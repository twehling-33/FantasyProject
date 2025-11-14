<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Home">
    <title>Home • Fantasy Futures - Fantasy Football Analytics</title>
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
                    <li><a href="/pages/trade_analyzer.php"  class="active">Fantasy Football Trade Calculator</a></li>
                    <li><a href="/pages/startsit.html">Start/Sit Analysis</a></li>
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
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
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

    $items = preg_split('/[\r\n,]+/', $s); //helps ot make all separators (new line or coma etc) the same
    return array_values(array_filter(array_map('trim', $items), function ($x) {
        return $x !== "";
    }));
}

function build_in_clause($prefix, $n) {
    // builds (?, ?, ...) list
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

function total_points($rows) {
    return array_sum(array_map(function ($r) {
        return (float)$r['fantasy_points'];
    }, $rows));
}

//input
$season     = isset($_POST['season']) ? (int)$_POST['season'] : 2024;
$weekStart  = isset($_POST['week_start']) ? (int)$_POST['week_start'] : 1;
$weekEnd    = isset($_POST['week_end']) ? (int)$_POST['week_end'] : 10;

$identTypeA = $_POST['ident_type_a'] ?? 'name'; // 'name' or 'id'
$identTypeB = $_POST['ident_type_b'] ?? 'name';

$listA = parse_list($_POST['team_a'] ?? "");
$listB = parse_list($_POST['team_b'] ?? "");

//query
$rowsA = fetch_side($pdo, $listA, $identTypeA, $season, $weekStart, $weekEnd);
$rowsB = fetch_side($pdo, $listB, $identTypeB, $season, $weekStart, $weekEnd);
$totA  = total_points($rowsA);
$totB  = total_points($rowsB);
$delta = $totA - $totB;

?>




<!doctype html>
// formatting
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Player Trade Analyzer (PPR)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body { font-family: system-ui, Arial, sans-serif; margin: 2rem; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        textarea { width: 100%; min-height: 120px; }
        fieldset { border: 1px solid #ddd; padding: 1rem; border-radius: .5rem; }
        legend { font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: .5rem; }
        th, td { border: 1px solid #ddd; padding: .5rem; text-align: left; }
        th { background: #f8f8f8; }
        .totals { font-weight: 700; }
        .delta { font-size: 1.1rem; margin-top: 1rem; }
        .pos { font-size: .9rem; color: #666; }
        .winner { padding: .25rem .5rem; border-radius: .4rem; background: #eef; display: inline-block; }
        .loser  { padding: .25rem .5rem; border-radius: .4rem; background: #fee; display: inline-block; }
        .controls { display:flex; gap:1rem; align-items:end; flex-wrap:wrap; }
        label { display:block; font-size:.9rem; margin-bottom:.25rem;}
        input[type="number"], select { padding:.4rem; width: 100%; }
        button { padding:.6rem 1rem; font-weight:600; }
        .muted { color:#777; font-size:.9rem; }
    </style>
</head>
<body>
<h1>Player Trade Analyzer (PPR)</h1>
<form method="post">
    <div class="controls">
        <div>
            <label>Season</label>
            <input type="number" name="season" min="2000" max="2100" value="<?=htmlspecialchars($season)?>">
        </div>
        <div>
            <label>Week start</label>
            <input type="number" name="week_start" min="1" max="18" value="<?=htmlspecialchars($weekStart)?>">
        </div>
        <div>
            <label>Week end</label>
            <input type="number" name="week_end" min="1" max="18" value="<?=htmlspecialchars($weekEnd)?>">
        </div>
        <div>
            <label>Team A identifiers</label>
            <select name="ident_type_a">
                <option value="name" <?= $identTypeA==='name'?'selected':'' ?>>Player names</option>
                <option value="id"   <?= $identTypeA==='id'  ?'selected':'' ?>>Player IDs</option>
            </select>
        </div>
        <div>
            <label>Team B identifiers</label>
            <select name="ident_type_b">
                <option value="name" <?= $identTypeB==='name'?'selected':'' ?>>Player names</option>
                <option value="id"   <?= $identTypeB==='id'  ?'selected':'' ?>>Player IDs</option>
            </select>
        </div>
        <div style="align-self:center;">
            <button type="submit">Analyze Trade</button>
        </div>
    </div>

    <div class="grid" style="margin-top:1rem;">
        <fieldset>
            <legend>Team A</legend>
            <textarea name="team_a" placeholder="One per line or comma-separated (e.g., Ja'Marr Chase, Joe Burrow)"><?=htmlspecialchars($_POST['team_a'] ?? "")?></textarea>
            <div class="muted">Enter <?= $identTypeA==='id' ? 'PlayerIDs' : 'Names' ?>; they must match the database.</div>
        </fieldset>
        <fieldset>
            <legend>Team B</legend>
            <textarea name="team_b" placeholder="One per line or comma-separated (e.g., Kyler Murray)"><?=htmlspecialchars($_POST['team_b'] ?? "")?></textarea>
            <div class="muted">Enter <?= $identTypeB==='id' ? 'PlayerIDs' : 'Names' ?>; they must match the database.</div>
        </fieldset>
    </div>
</form>

<?php if (!empty($listA) || !empty($listB)) : ?>
    <div class="grid" style="margin-top:1.25rem;">
        <div>
            <h2>Team A (Weeks <?=$weekStart?>–<?=$weekEnd?>, <?=$season?>)</h2>
            <table>
                <tr><th>Player</th><th>Pos</th><th>PPR Points</th></tr>
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
        <div>
            <h2>Team B (Weeks <?=$weekStart?>–<?=$weekEnd?>, <?=$season?>)</h2>
            <table>
                <tr><th>Player</th><th>Pos</th><th>PPR Points</th></tr>
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
    </div>

    <div class="delta">
        <?php if ($delta > 0): ?>
            <span class="winner">Team A +<?=number_format($delta, 2)?> PPR</span>
        <?php elseif ($delta < 0): ?>
            <span class="winner">Team B +<?=number_format(abs($delta), 2)?> PPR</span>
        <?php else: ?>
            <span class="loser">Even trade (0.00)</span>
        <?php endif; ?>
    </div>
<?php endif; ?>

<p class="muted" style="margin-top:1.25rem;">
    Notes: PPR scoring = 1/rec, 0.1 per rush/rec yard, 6 per rush/rec TD, 0.04 per pass yard, 4 per pass TD.
    You can adjust the weights directly in the SQL SUM() if your league settings differ.
</p>
</body>
</html>
