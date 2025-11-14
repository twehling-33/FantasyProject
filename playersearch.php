<?php

const DEBUG = true;

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
$week     = isset($_GET['week']) ? trim($_GET['week']) : '';
$position = isset($_GET['position']) ? trim($_GET['position']) : '';
$team     = isset($_GET['team']) ? trim($_GET['team']) : '';

// --- BUILD SQL QUERY DYNAMICALLY ---

$sql = "SELECT 
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
        WHERE 1 = 1";

$params = [];

// Filters
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

// Order & limit (so you don’t accidentally dump thousands of rows)
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
    <meta name="description" content="About">
    <title>About • Fantasy Futures - Fantasy Football Analytics</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<header class="header">
    <div class="container">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
            <a href="../index.html" style="font-weight:800; font-size:1.8rem;">Fantasy Futures - Fantasy Football Analytics</a>
            <nav aria-label="Main">
                <ul>
                    <li><a href="../index.php" >Home</a></li>
                    <li><a href="/pages/playersearch.php"  class="active">Player Search</a></li>
                    <li><a href="/pages/trade_analyzer.php">Fantasy Football Trade Calculator</a></li>
                    <li><a href="/pages/startsit.html">Start/Sit Analysis</a></li>
                    <li><a href="./about.html">About</a></li>
                    <li><a href="./contact.html" >Contact</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<div class="container">
    <h2>Player Stats Lookup</h2>
    <p>Search 2024 weekly stats by player, week, position, and team.</p>

    <div class="search-card">
        <form method="get" action="playersearch.php">
            <div class="search-row">
                <div class="field-group">
                    <label for="name">Player Name</label>
                    <input
                            type="text"
                            id="name"
                            name="name"
                            placeholder="e.g. Allen, Hill, Jefferson"
                            value="<?php echo htmlspecialchars($name); ?>"
                    >
                </div>

                <div class="field-group">
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

                <div class="field-group">
                    <label for="position">Position</label>
                    <select id="position" name="position">
                        <option value="">All Positions</option>
                        <?php
                        $positions = ['QB', 'RB', 'WR', 'TE'];
                        foreach ($positions as $pos): ?>
                            <option value="<?php echo $pos; ?>"
                                    <?php if ($position === $pos) echo 'selected'; ?>>
                                <?php echo $pos; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
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

            <button type="submit">Search Stats</button>
        </form>
    </div>

    <?php if (!empty($players)): ?>
        <div class="results-info">
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

        <div style="overflow-x:auto; max-height: 70vh;">
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
    <?php else: ?>
        <p class="no-results">
            No results yet. Try searching by player name (e.g. “Allen”, “Jefferson”) and/or selecting a week.
        </p>
    <?php endif; ?>
</div>
</body>
</html>
