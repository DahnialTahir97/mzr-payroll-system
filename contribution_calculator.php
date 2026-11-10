<?php
require_once "config/database.php";

/*
 * MZR FOOD SERVICES - statutory contribution lookup.
 * Reads only rates entered in contribution_rates; never estimates missing rates.
 */
function getContribution(PDO $conn, string $type, string $category, float $wage, string $salaryMonth): array {
    $date = $salaryMonth . "-01";
    $stmt = $conn->prepare(
        "SELECT employee_amount, employer_amount
         FROM contribution_rates
         WHERE contribution_type = ? AND scheme_category = ?
           AND min_wage <= ? AND max_wage >= ?
           AND effective_from <= ?
           AND (effective_until IS NULL OR effective_until >= ?)
         ORDER BY effective_from DESC LIMIT 1"
    );
    $stmt->execute([$type, $category, $wage, $wage, $date, $date]);
    $rate = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$rate) {
        throw new Exception("Jadual rasmi $type tiada untuk kategori $category dan upah RM" . number_format($wage, 2) . ". Sila masukkan jadual yang sah sebelum menjana payroll.");
    }
    return [
        'employee' => (float)$rate['employee_amount'],
        'employer' => (float)$rate['employer_amount']
    ];
}

function calculateStatutoryContributions(PDO $conn, array $categories, float $wage, string $salaryMonth): array {
    if ($wage < 0) throw new Exception("Jumlah upah tidak sah.");

    $epf = getContribution($conn, 'KWSP', $categories['KWSP'], $wage, $salaryMonth);
    $socso = getContribution($conn, 'PERKESO', $categories['PERKESO'], $wage, $salaryMonth);

    // SIP yang tidak layak tidak memerlukan rekod kadar atau potongan.
    if (($categories['SIP'] ?? '') === 'SIP_NOT_ELIGIBLE') {
        $eis = ['employee' => 0.00, 'employer' => 0.00];
    } else {
        $eis = getContribution($conn, 'SIP', $categories['SIP'], $wage, $salaryMonth);
    }

    return [
        'epf_employee' => $epf['employee'], 'epf_employer' => $epf['employer'],
        'socso_employee' => $socso['employee'], 'socso_employer' => $socso['employer'],
        'eis_employee' => $eis['employee'], 'eis_employer' => $eis['employer'],
        'total_employee_contribution' => $epf['employee'] + $socso['employee'] + $eis['employee'],
        'total_employer_contribution' => $epf['employer'] + $socso['employer'] + $eis['employer']
    ];
}
