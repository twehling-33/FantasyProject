<?php
/* comment out for degguggin
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 */


$DB_HOST = "sql302.thsite.top";
$DB_NAME = "thsi_40395189_PlayerStats";
$DB_USER = "thsi_40395189";
$DB_PASS = "RphibGT1";

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (Exception $e) {
    http_response_code(500);
    echo "DB connection error: " . htmlspecialchars($e->getMessage());
    exit;
}

function parse_list($s) {
    $items = preg_split('/[\r\n,]+/', $s);
    return array_values(array_filter(array_map('trim', $items), function ($x) {
        return $x !== "";
    }));
}

function build_in_clause($prefix, $n) {
    return $prefix . " IN (" . implode(",", array_fill(0, $n, "?")) . ")";
}

function fetch_side($pdo, $identifiers, $identifierType, $season, $weekStart, $weekEnd) {
    if (empty($identifiers)) return [];

    $idCol = $identifierType === 'id' ? 'PlayerID' : 'Name';

    $sql = "
      SELECT
        PlayerID,
        Name,
        Position,
        SUM(
          (Receptions*1.0) +
          (ReceivingYards*0.1) +
          (ReceivingTouchdowns*6) +
          (RushingYards*0.1) +
          (RushingTouchdowns*6) +
          (PassingYards*0.04) +
          (PassingTouchdowns*4)
        ) AS fantasy_points
      FROM player_stats_season
      WHERE Season = ?
        AND Week BETWEEN ? AND ?
        AND " . build_in_clause($idCol, count($identifiers)) . "
      GROUP BY PlayerID, Name, Position
      ORDER BY fantasy_points DESC
    ";

    $params = array_merge([$season, $weekStart, $weekEnd], $identifiers);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_projected_side_by_week($pdo, $identifiers, $identifierType, $season, $weekTrade) {
    if (empty($identifiers)) return [];

    $idCol = $identifierType === 'id' ? 'PlayerID' : 'Name';

    $weekStart = $weekTrade + 1;
    $weekEnd   = 18;

    if ($weekStart > $weekEnd) {
        return [];
    }

    // Use precomputed points
    $sql = "
      SELECT
        Week,
        SUM(FantasyPointsPPR) AS fantasy_points
      FROM player_stats_projections
      WHERE Season = ?
        AND Week BETWEEN ? AND ?
        AND " . build_in_clause($idCol, count($identifiers)) . "
      GROUP BY Week
      ORDER BY Week
    ";

    $params = array_merge([$season, $weekStart, $weekEnd], $identifiers);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();



    /*  comment in for debugging
    $rows = $stmt->fetchAll();
    echo '<pre>';
    echo "DEBUG projections: season=$season, weekStart=$weekStart, weekEnd=$weekEnd\n";
    echo "DEBUG identifiers (" . $idCol . "):\n";
    print_r($identifiers);
    echo "DEBUG rows:\n";
    print_r($rows);
    echo '</pre>';

    return $rows;
    */
}


function total_points($rows) {
    return array_sum(array_map(function ($r) {
        return (float)$r['fantasy_points'];
    }, $rows));
}

// ---- INPUTS ----
$season     = isset($_POST['season']) ? (int)$_POST['season'] : 2024;
$weekStart  = 1;
$weekEnd    = isset($_POST['week_end']) ? (int)$_POST['week_end'] : 10;

$identTypeA = 'name';
$identTypeB = 'name';

$listA = parse_list($_POST['team_a'] ?? "");
$listB = parse_list($_POST['team_b'] ?? "");

// ---- HISTORICAL ----
$rowsA = fetch_side($pdo, $listA, $identTypeA, $season, $weekStart, $weekEnd);
$rowsB = fetch_side($pdo, $listB, $identTypeB, $season, $weekStart, $weekEnd);
$totA  = total_points($rowsA);
$totB  = total_points($rowsB);
$delta = $totA - $totB;

// ---- FUTURE PROJECTIONS ----
$projRowsA = fetch_projected_side_by_week($pdo, $listA, $identTypeA, $season, $weekEnd);
$projRowsB = fetch_projected_side_by_week($pdo, $listB, $identTypeB, $season, $weekEnd);

$projAByWeek = [];
foreach ($projRowsA as $r) {
    $projAByWeek[(int)$r['Week']] = (float)$r['fantasy_points'];
}

$projBByWeek = [];
foreach ($projRowsB as $r) {
    $projBByWeek[(int)$r['Week']] = (float)$r['fantasy_points'];
}

// Build weeks and data arrays
$futureWeeks = [];
for ($w = $weekEnd + 1; $w <= 18; $w++) {
    $futureWeeks[] = $w;
}

$chartLabels = $futureWeeks;
$chartDataA  = [];
$chartDataB  = [];

foreach ($futureWeeks as $w) {
    $chartDataA[] = $projAByWeek[$w] ?? 0.0;
    $chartDataB[] = $projBByWeek[$w] ?? 0.0;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Home">
    <title>Trade Analyzer • Fantasy Futures - Fantasy Football Analytics</title>
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header class="header">
    <div class="container">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
            <a href="index.php" style="font-weight:800; font-size:1.8rem;">Fantasy Futures - Fantasy Football Analytics</a>
            <nav aria-label="Main">
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="/pages/playersearch.php">Player Search</a></li>
                    <li><a href="/pages/trade_analyzer.php"  class="active">Fantasy Football Trade Calculator</a></li>
                    <li><a href="/pages/startsit.php">Start/Sit Analysis</a></li>
                    <li><a href="/pages/about.html">About</a></li>
                    <li><a href="/pages/contact.html">Contact</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<main class="container">

    <div class="container">
        <section class="hero card">
            <h1>Player Trade Analyzer (PPR)</h1>
            <p>Compare value of a trade based PPR production to the current week.</p>
            <p>Based off of PPR scoring = 1/rec, 0.1 per rush/rec yard, 6 per rush/rec TD, 0.04 per pass yard,
                4 per pass TD.</p>
        </section>
    </div>

    <div class="container">
        <section class="card">
            <form method="post">
                <div class="controls">
                    <div>
                        <label>Season</label>
                        <input type="number" name="season" min="2000" max="2100" value="<?=htmlspecialchars($season)?>">
                    </div>

                    <div>
                        <label>Current Week</label>
                        <input type="number" name="week_end" min="1" max="18" value="<?=htmlspecialchars($weekEnd)?>">
                    </div>
                    <div style="align-self:center;">
                        <button type="submit" class="button">Analyze Trade</button>
                    </div>
                </div>

                <div class="grid" style="margin-top:1rem;">
                    <fieldset>
                        <legend>Team A</legend>
                        <textarea name="team_a" placeholder="One per line or comma-separated (e.g., Ja'Marr Chase, Joe Burrow)"><?=htmlspecialchars($_POST['team_a'] ?? "")?></textarea>
                        <div class="muted">
                            Enter player names; they must match the database.
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Team B</legend>
                        <textarea name="team_b" placeholder="One per line or comma-separated (e.g., Kyler Murray)"><?=htmlspecialchars($_POST['team_b'] ?? "")?></textarea>
                        <div class="muted">
                            Enter player names; they must match the database.
                        </div>
                    </fieldset>
                </div>
            </form>
        </section>
    </div>

    <?php if (!empty($listA) || !empty($listB)) : ?>
        <div class="container">
            <section class="grid" style="margin-top:1.25rem;">
                <div class="card">
                    <h2>Team A (Weeks <?=$weekStart?>–<?=$weekEnd?>, <?=$season?>)</h2>
                    <table>
                        <tr><th>Player</th><th>Pos</th><th>PPR Points to selected week</th></tr>
                        <?php foreach ($rowsA as $r): ?>
                            <tr>
                                <td><?=htmlspecialchars($r['Name'])?></td>
                                <td class="pos"><?=htmlspecialchars($r['Position'])?></td>
                                <td><?=number_format($r['fantasy_points'], 2)?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="totals"><td colspan="2">Total</td><td><?=number_format($totA, 2)?></td></tr>
                    </table>
                </div>

                <div class="card">
                    <h2>Team B (Weeks <?=$weekStart?>–<?=$weekEnd?>, <?=$season?>)</h2>
                    <table>
                        <tr><th>Player</th><th>Pos</th><th>PPR Points to selected week</th></tr>
                        <?php foreach ($rowsB as $r): ?>
                            <tr>
                                <td><?=htmlspecialchars($r['Name'])?></td>
                                <td class="pos"><?=htmlspecialchars($r['Position'])?></td>
                                <td><?=number_format($r['fantasy_points'], 2)?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="totals"><td colspan="2">Total</td><td><?=number_format($totB, 2)?></td></tr>
                    </table>
                </div>
            </section>
        </div>

        <section class="card">
            <div class="delta" style="text-align:center;">
                <?php if ($delta > 0): ?>
                    <span class="winner">Team A +<?=number_format($delta, 2)?> PPR</span>
                <?php elseif ($delta < 0): ?>
                    <span class="winner">Team B +<?=number_format(abs($delta), 2)?> PPR</span>
                <?php else: ?>
                    <span class="loser">Even trade (0.00)</span>
                <?php endif; ?>
            </div>
        </section>

    <?php if (!empty($futureWeeks)): ?>
        <div class="container">
            <section class="card">
                <h2>Projected Weekly PPR After Trade (Weeks <?=$weekEnd+1?>–18)</h2>
                <p class="muted">
                    Uses your projections table to sum expected PPR for all players on each side.
                </p>
                <canvas id="projectionChart" height="120"></canvas>
            </section>
        </div>

        <script>
            const projLabels = <?=json_encode($chartLabels)?>;
            const projDataA  = <?=json_encode($chartDataA)?>;
            const projDataB  = <?=json_encode($chartDataB)?>;

            const ctx = document.getElementById('projectionChart').getContext('2d');
            const projectionChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: projLabels,
                    datasets: [
                        {label: 'Team A projected PPR', data: projDataA, borderWidth: 2, tension: 0.3, pointRadius: 3},
                        {label: 'Team B projected PPR', data: projDataB, borderWidth: 2, tension: 0.3, pointRadius: 3}
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {title: {display: true, text: 'Week'}},
                        y: {title: {display: true, text: 'Projected PPR'}, beginAtZero: true}
                    },
                    plugins: {tooltip: {mode: 'index', intersect: false}, legend: {position: 'bottom'}}
                }
            });
        </script>
    <?php endif; ?>
    <?php endif; ?>

    <div class="footer">
        © <span id="year"></span> Fantasy Futures · Built with HTML, CSS, MySQL &amp; PHP
    </div>
</main>

<script>
    document.getElementById('year').textContent = new Date().getFullYear();
</script>
</body>
</html>
