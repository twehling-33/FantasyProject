
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Start/Sit Analysis">
    <title>Start/Sit • Fantasy Futures - Fantasy Football Analytics</title>
    <link rel="stylesheet" href="../style.css">
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
                    <li><a href="/pages/trade_analyzer.php">Fantasy Football Trade Calculator</a></li>
                    <li><a href="/pages/startsit.php" class="active">Start/Sit Analysis</a></li>
                    <li><a href="/pages/about.html">About</a></li>
                    <li><a href="/pages/contact.html">Contact</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<?php

$DB_HOST = "sql302.thsite.top";
$DB_NAME = "thsi_40395189_PlayerStats";
$DB_USER = "thsi_40395189";
$DB_PASS = "RphibGT1";

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (Exception $e) {
    http_response_code(500);
    echo "DB connection error.";
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


function fetch_side($pdo, $identifiers, $identifierType, $season, $week) {
    if (empty($identifiers)) return [];

    $idCol = $identifierType === 'id' ? 'PlayerID' : 'Name';

    $sql = "
        SELECT
            PlayerID,
            Name,
            Position,
            (
                (IFNULL(Receptions, 0) * 1.0) +
                (IFNULL(ReceivingYards, 0) * 0.1) +
                (IFNULL(ReceivingTouchdowns, 0) * 6) +
                (IFNULL(RushingYards, 0) * 0.1) +
                (IFNULL(RushingTouchdowns, 0) * 6) +
                (IFNULL(PassingYards, 0) * 0.04) +
                (IFNULL(PassingTouchdowns, 0) * 4)
            ) AS fantasy_points
        FROM player_stats_projections
        WHERE Season = ?
          AND Week = ?
          AND " . build_in_clause($idCol, count($identifiers)) . "
        ORDER BY fantasy_points DESC
    ";


    $params = array_merge([$season, $week], $identifiers);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function total_points($rows) {
    return array_sum(array_map(function ($r) {
        return (float)$r['fantasy_points'];
    }, $rows));
}


$season    = isset($_POST['season']) ? (int)$_POST['season'] : 2024;
$week      = isset($_POST['week_end']) ? (int)$_POST['week_end'] : 10;
$identTypeA = 'name';
$identTypeB = 'name';

$listA = parse_list($_POST['team_a'] ?? "");
$listB = parse_list($_POST['team_b'] ?? "");


$rowsA = fetch_side($pdo, $listA, $identTypeA, $season, $week);
$rowsB = fetch_side($pdo, $listB, $identTypeB, $season, $week);
$totA  = total_points($rowsA);
$totB  = total_points($rowsB);
$delta = $totA - $totB;

?>

<main class="container">

    <div class="container">
        <section class="hero card">
            <h1>Start/Sit Analyzer</h1>
            <p>Compare two players’ <strong>projected PPR scores</strong> to decide who to start and who to sit.</p>
            <p>
                Scoring (PPR): 1 per reception, 0.1 per rush/rec yard, 6 per rush/rec TD,
                0.04 per pass yard, 4 per pass TD.
            </p>
        </section>
    </div>

    <div class="container">
        <section class="card">
            <form method="post">
                <div class="controls">
                    <div>
                        <label>Season</label>
                        <input type="number" name="season" min="2000" max="2100"
                               value="<?=htmlspecialchars($season)?>">
                    </div>

                    <div>
                        <label>Week (projection)</label>
                        <input type="number" name="week_end" min="1" max="18"
                               value="<?=htmlspecialchars($week)?>">
                    </div>
                    <div style="align-self:center;">
                        <button type="submit" class="button">Analyze Start/Sit</button>
                    </div>
                </div>

                <div class="grid" style="margin-top:1rem;">
                    <fieldset>
                        <legend>Player A</legend>
                        <textarea name="team_a"
                                  placeholder="Player Name (e.g., Ja'Marr Chase)"><?=htmlspecialchars($_POST['team_a'] ?? "")?></textarea>
                        <div class="muted">
                            Enter player name; must match the projections table.
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend>Player B</legend>
                        <textarea name="team_b"
                                  placeholder="Player Name (e.g., Tee Higgins)"><?=htmlspecialchars($_POST['team_b'] ?? "")?></textarea>
                        <div class="muted">
                            Enter player name; must match the projections table.
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
                    <h2>Player A – Projected (Week <?=$week?>, <?=$season?>)</h2>
                    <table>
                        <tr><th>Player</th><th>Pos</th><th>Projected PPR</th></tr>
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
                    <h2>Player B – Projected (Week <?=$week?>, <?=$season?>)</h2>
                    <table>
                        <tr><th>Player</th><th>Pos</th><th>Projected PPR</th></tr>
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
                    <span class="winner">Player A +<?=number_format($delta, 2)?> projected PPR</span>
                <?php elseif ($delta < 0): ?>
                    <span class="winner">Player B +<?=number_format(abs($delta), 2)?> projected PPR</span>
                <?php else: ?>
                    <span class="loser">Even (0.00 projected PPR difference)</span>
                <?php endif; ?>
            </div>
        </section>
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
