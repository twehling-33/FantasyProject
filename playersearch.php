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


$name     = isset($_GET['name']) ? trim($_GET['name']) : '';
$week     = isset($_GET['week']) ? (string)trim($_GET['week']) : '';
$position = isset($_GET['position']) ? trim($_GET['position']) : '';
$team     = isset($_GET['team']) ? trim($_GET['team']) : '';


$sql = "
  SELECT
    Season,
    Week,
    PlayerID,
    Name,
    Team,
    Opponent,
    HomeOrAway,
    Position,
    Receptions,
    ReceivingYards,
    ReceivingTouchdowns,
    RushingYards,
    RushingTouchdowns,
    PassingYards,
    PassingTouchdowns,
    TotalYards,
    TotalTDs
  FROM player_stats_season
  WHERE 1 = 1
";

$params = [];


if ($name !== '') {
    $sql .= " AND Name LIKE :name";
    $params[':name'] = '%' . $name . '%';
}

if ($week !== '') {
    $sql .= " AND Week = :week";
    $params[':week'] = (int)$week;
}

if ($position !== '') {
    $sql .= " AND Position = :position";
    $params[':position'] = $position;
}

if ($team !== '') {
    $sql .= " AND Team = :team";
    $params[':team'] = strtoupper($team);
}

$sql .= " ORDER BY Week, TotalYards DESC, Name LIMIT 300";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$players = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Player Search">
    <title>Player Search • Fantasy Futures - Fantasy Football Analytics</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<header class="header">
    <div class="container">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
            <a href="../index.php" style="font-weight:800; font-size:1.8rem;">Fantasy Futures - Fantasy Football Analytics</a>
            <nav aria-label="Main">
                <ul>
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="/pages/playersearch.php" class="active">Player Search</a></li>
                    <li><a href="/pages/trade_analyzer.php">Fantasy Football Trade Calculator</a></li>
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
        <h1>Player Stats Lookup</h1>
        <p>Search 2024 stats by player, week, position, and team.</p>
    </section>
    </div>

    <div class="container">
    <section class="card">
        <form method="get" action="playersearch.php">
            <div class="grid" style="gap:1rem;">
                <div>
                    <label for="name">Player Name</label>
                    <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="e.g. Allen, Hill, Jefferson"
                            value="<?php echo htmlspecialchars($name); ?>"
                    >
                </div>

                <div>
                    <label for="week">Week</label>
                    <select id="week" name="week">
                        <option value="">All Weeks</option>
                        <?php for ($w = 1; $w <= 18; $w++): ?>
                            <option value="<?php echo $w; ?>"
                                <?php if ($week !== '' && (int)$week === $w) echo 'selected'; ?>>
                                Week <?php echo $w; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div>
                    <label for="position">Position</label>
                    <select id="position" name="position">
                        <option value="">All Positions</option>
                        <?php foreach (['QB','RB','WR','TE'] as $pos): ?>
                            <option value="<?php echo $pos; ?>" <?php if ($position === $pos) echo 'selected'; ?>>
                                <?php echo $pos; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="team">Team (abbr)</label>
                    <input
                            type="text"
                            id="team"
                            name="team"
                            placeholder="KC, SF, DAL"
                            value="<?php echo htmlspecialchars($team); ?>"
                    >
                </div>
            </div>

            <div style="margin-top:1rem;">
                <button type="submit" class="button">Search Stats</button>
            </div>
        </form>
    </section>
    </div>

    <?php if (!empty($_GET) && !empty($players)): ?>
    <div class="container">
        <section class="card">
            <div class="results-info" style="margin-bottom:0.75rem;">
                Showing <?php echo count($players); ?> result(s)
                <?php if ($week !== ''): ?>
                    <span class="tag">Week <?php echo htmlspecialchars($week); ?></span>
                <?php endif; ?>
                <?php if ($position !== ''): ?>
                    <span class="tag"><?php echo htmlspecialchars($position); ?></span>
                <?php endif; ?>
                <?php if ($team !== ''): ?>
                    <span class="tag"><?php echo htmlspecialchars(strtoupper($team)); ?></span>
                <?php endif; ?>
            </div>

            <div style="overflow-x:auto; max-height:70vh;">
                <table>
                    <thead>
                    <tr>
                        <th>Season</th>
                        <th>Week</th>
                        <th>Player</th>
                        <th>Pos</th>
                        <th>Team</th>
                        <th>Opp</th>
                        <th>H/A</th>
                        <th>Rec</th>
                        <th>Rec Yds</th>
                        <th>Rec TD</th>
                        <th>Rush Yds</th>
                        <th>Rush TD</th>
                        <th>Pass Yds</th>
                        <th>Pass TD</th>
                        <th>Total Yds</th>
                        <th>Total TD</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($players as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Season']); ?></td>
                            <td><?php echo htmlspecialchars($row['Week']); ?></td>
                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Position']); ?></td>
                            <td><?php echo htmlspecialchars($row['Team']); ?></td>
                            <td><?php echo htmlspecialchars($row['Opponent']); ?></td>
                            <td><?php echo htmlspecialchars($row['HomeOrAway']); ?></td>
                            <td><?php echo htmlspecialchars($row['Receptions']); ?></td>
                            <td><?php echo htmlspecialchars($row['ReceivingYards']); ?></td>
                            <td><?php echo htmlspecialchars($row['ReceivingTouchdowns']); ?></td>
                            <td><?php echo htmlspecialchars($row['RushingYards']); ?></td>
                            <td><?php echo htmlspecialchars($row['RushingTouchdowns']); ?></td>
                            <td><?php echo htmlspecialchars($row['PassingYards']); ?></td>
                            <td><?php echo htmlspecialchars($row['PassingTouchdowns']); ?></td>
                            <td><?php echo htmlspecialchars($row['TotalYards']); ?></td>
                            <td><?php echo htmlspecialchars($row['TotalTDs']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <?php elseif (!empty($_GET)): ?>
        <section class="card">
            <p class="no-results">No results found. Try another name (e.g. “Allen”, “Jefferson”) or adjust filters.</p>
        </section>
    <?php else: ?>

        <section class="card">
            <h2>How to use</h2>
            <p>Enter a player name (partial OK), optionally choose a week, position, and team, then hit <em>Search Stats</em>.</p>
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