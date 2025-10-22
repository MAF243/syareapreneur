<?php
/**
 * Create loan + monthly schedule from an approved P2MU form.
 *
 * Assumptions (silakan sesuaikan nama kolom):
 *  - p2mu_forms: id, user_id, jumlah_pinjaman (BIGINT), tenor_bulan (INT), margin_flat_persen (DECIMAL) nullable,
 *                tanggal_pengajuan (DATE), tanggal_disetujui (DATE nullable)
 *  - loans:      lihat DDL yang sudah kita buat sebelumnya
 *  - loan_installments:  idem
 *
 * @param mysqli $conn
 * @param int    $formId  id row p2mu_forms yang statusnya sudah "diterima"
 * @param string $startPolicy  'next-month-1' (default) atau 'approval-date'
 * @return int|false  loan_id yang dibuat atau false jika gagal
 */
function createLoanFromP2MU(mysqli $conn, int $formId, string $startPolicy='next-month-1') {
    // 1) Ambil form
    $sql = "SELECT id, user_id, jumlah_pinjaman, tenor_bulan,
                   COALESCE(margin_flat_persen, 0) AS margin_flat_persen,
                   COALESCE(tanggal_disetujui, CURDATE()) AS tanggal_disetujui
            FROM p2mu_forms WHERE id=? LIMIT 1";
    if (!$stmt = $conn->prepare($sql)) return false;
    $stmt->bind_param("i", $formId);
    $stmt->execute();
    $form = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$form) return false;

    $userId       = (int)$form['user_id'];
    $principal    = (int)$form['jumlah_pinjaman'];
    $tenor        = max(1, (int)$form['tenor_bulan']);
    $aprFlatPct   = (float)$form['margin_flat_persen'];   // contoh: 12 = 12% total tenor (flat)
    $approvedDate = $form['tanggal_disetujui'];

    // 2) Hitung margin total flat buat 1 tenor (kalau tidak ada di form, pakai 0)
    $marginTotal  = (int) round($principal * ($aprFlatPct/100.0));

    // 3) Tentukan tanggal mulai cicilan
    if ($startPolicy === 'approval-date') {
        $startDate = new DateTime($approvedDate);         // cicilan pertama di tanggal ini
    } else {
        // default: tanggal 1 bulan depan
        $d = new DateTime($approvedDate);
        $d->modify('first day of next month');
        $startDate = $d;
    }

    // 4) Simpan loans
    $sqlLoan = "INSERT INTO loans (user_id, form_id, principal, margin_total, tenor_months, start_date, status)
                VALUES (?,?,?,?,?, ?, 'active')";
    if (!$stmt = $conn->prepare($sqlLoan)) return false;
    $formIdInt = (int)$form['id'];
    $startDateStr = $startDate->format('Y-m-d');
    $stmt->bind_param("iiiiis", $userId, $formIdInt, $principal, $marginTotal, $tenor, $startDateStr);
    if (!$stmt->execute()) { $stmt->close(); return false; }
    $loanId = $stmt->insert_id;
    $stmt->close();

    // 5) Breakdown angsuran flat per bulan
    //    Total dibayar = principal + marginTotal
    $totalToPay   = $principal + $marginTotal;
    $baseAmount   = intdiv($totalToPay, $tenor);            // pembulatan kebawah
    $remainder    = $totalToPay - ($baseAmount * $tenor);   // sisanya dibagi ke bulan awal
    $principalPer = intdiv($principal, $tenor);
    $marginPer    = intdiv($marginTotal, $tenor);
    $principalRem = $principal - ($principalPer * $tenor);
    $marginRem    = $marginTotal - ($marginPer * $tenor);

    // 6) Buat jadwal bulanan
    $sqlIns = "INSERT INTO loan_installments
               (loan_id, user_id, due_date, amount, principal_part, margin_part, status)
               VALUES (?,?,?,?,?,?, 'unpaid')";
    if (!$stmt = $conn->prepare($sqlIns)) return false;

    $due = clone $startDate;
    for ($i=0; $i<$tenor; $i++) {
        // bagi sisa ke bulan-bulan awal biar total presisi
        $amount   = $baseAmount   + ($i < $remainder ? 1 : 0);
        $pPart    = $principalPer + ($i < $principalRem ? 1 : 0);
        $mPart    = $marginPer    + ($i < $marginRem ? 1 : 0);

        $dueStr = $due->format('Y-m-d');
        $stmt->bind_param("iiisii", $loanId, $userId, $dueStr, $amount, $pPart, $mPart); // types fix later
        // ^^ Perhatikan: due_date bertipe DATE, jadi harus "s", bukan "i".
        // Perbaiki bind types:
        $stmt->bind_param("iiisii", $loanId, $userId, $amount, $dueStr, $pPart, $mPart);
        // -> Yang benar:
    }
    // Perbaiki bind types benar (baris di atas kita rapikan):
    $stmt->close();

    // Siapkan ulang statement dengan bind yang benar
    $stmt = $conn->prepare($sqlIns);
    for ($i=0; $i<$tenor; $i++) {
        $amount   = $baseAmount   + ($i < $remainder ? 1 : 0);
        $pPart    = $principalPer + ($i < $principalRem ? 1 : 0);
        $mPart    = $marginPer    + ($i < $marginRem ? 1 : 0);
        $dueStr   = $startDate->modify($i === 0 ? '+0 month' : '+1 month')->format('Y-m-d');

        $stmt->bind_param("iiisii", $loanId, $userId, $amount, $dueStr, $pPart, $mPart);
        // order kolom: (loan_id i, user_id i, due_date s, amount i, principal_part i, margin_part i)
        // Tapi query kita: (... due_date, amount, principal_part, margin_part)
        // Jadi urutan bind HARUS: i (loan_id) , i (user_id) , s (due_date) , i (amount) , i (pPart) , i (mPart)
        $stmt->bind_param("iisiii", $loanId, $userId, $dueStr, $amount, $pPart, $mPart);
        if (!$stmt->execute()) { $stmt->close(); return false; }
    }
    $stmt->close();

    return (int)$loanId;
}
