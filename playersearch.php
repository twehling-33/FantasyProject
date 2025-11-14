<?php
require 'config.php';

// Read filters from query string
$name     = isset($_GET['name']) ? trim($_GET['name']) : '';
$week     = isset($_GET['week']) ? trim($_GET['week']) : '';
$position = isset($_GET['position']) ? trim($_GET['position']) : '';
$team     = isset($_GET['team']) ? trim($_GET['team']) : '';

// Build SQL query dynamically
$sql = "SELECT Season, Week, PlayerID, Name, Team, Opponent, HomeOrAway, Position,
               Receptions, ReceivingYards, ReceivingTouchdowns,
               RushingYards, RushingTouchdowns,
               PassingYards, PassingTouchdowns,
               TotalYards, TotalTDs
        FROM PlayerStats
        WHERE 1=1";

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

$sql .= " ORDER BY Week, TotalYards DESC, Name LIMIT 300";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Player Stats Search</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #0b1120;
            color: #e5e7eb;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #111827;
            padding: 15px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        header h1 {
            margin: 0;
            font-size: 1.6rem;
            color: #f97316;
        }
        nav a {
            color: #e5e7eb;
            margin-left: 15px;
            text-decoration: none;
            font-size: 0.95rem;
        }
        nav a:hover { text-decoration: underline; }

        .container {
            max-width: 1150px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .search-card {
            background-color: #111827;
            padding: 15px 20px;
            border-radius: 10px;
            border: 1px solid #1f2937;
            margin-bottom: 25px;
        }

        .search-row {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 150px;
        }

        label {
            font-size: 0.85rem;
            margin-bottom: 3px;
            color: #9ca3af;
        }

        input[type="text"],
        select {
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid #4b5563;
            background-color: #020617;
            color: #e5e7eb;
            font-size: 0.9rem;
        }

        button[type="submit"] {
            margin-top: 18px;
            padding: 8px 14px;
            border-radius: 6px;
            border: none;
            background-color: #f97316;
            color: #111827;
            font-weight: bold;
            cursor: pointer;
        }
        button[type="submit"]:hover {
            background-color: #fb923c;
        }

        .results-info {
            font-size: 0.9rem;
            color: #9ca3af;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        th, td {
            padding: 6px 8px;
            border-bottom: 1px solid #1f2937;
            text-align: right;
        }
        th {
            background-color: #020617;
            position: sticky;
            top: 0;
            z-index: 1;
        }
        th:first-child, td:first-child,
        th:nth-child(2), td:nth-child(2),
        th:nth-child(3), td:nth-child(3),
        th:nth-child(4), td:nth-child(4) {
            text-align: left;
        }

        .no-results {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #9ca3af;
        }

        .tag {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-left: 6px;
        }
    </style>
</head>

<body>
<header>
    <h1>Fantasy Toolbox</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="startsit.php">Start/Sit</a>
        <a href="tradecalc.php">Trade Calculator</a>
        <a href="playersearch.php"><strong>Player Stats</strong></a>
    </nav>
</header>

<div class="container">
    <h2>Player Stats Lookup</h2>

    <div class="search-card">
        <form method="get" action="playersearch.php">
            <div class="search-row">

                <div class="field-group">
                    <label>Player Name</label>
                    <input type="text" name="name" placeholder="e.g. Allen, Hill, Jefferson"
                           value="<?php echo htmlspecialchars($name); ?>">
                </div>

                <div class="field-group">
                    <label>Week</label>
                    <select name="week">
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
                    <label>Position</label>
                    <select name="position">
                        <option value="">All Positions</option>
                        <?php
                        $positions = ['QB', 'RB', 'WR', 'TE'];
                        foreach ($positions as $p):
                            ?>
                            <option value="<?php echo $p; ?>"
                                    <?php if ($position === $p) echo 'selected'; ?>>
                                <?php echo $p; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="field-group">
                    <label>Team</label>
                    <input type="text" name="team" placeholder="KC, DAL, SF"
                           value="<?php echo htmlspecialchars($team); ?>">
                </div>

            </div>

            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (!empty($players)): ?>
        <div class="results-info">
            Showing <?php echo count($players); ?> result(s)
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
                <?php foreach ($players as $p): ?>
                    <tr>
                        <td><?php echo $p['Season']; ?></td>
                        <td><?php echo $p['Week']; ?></td>
                        <td><?php echo htmlspecialchars($p['Name']); ?></td>
                        <td><?php echo $p['Position']; ?></td>
                        <td><?php echo $p['Team']; ?></td>
                        <td><?php echo $p['Opponent']; ?></td>
                        <td><?php echo $p['HomeOrAway']; ?></td>
                        <td><?php echo $p['Receptions']; ?></td>
                        <td><?php echo $p['ReceivingYards']; ?></td>
                        <td><?php echo $p['ReceivingTouchdowns']; ?></td>
                        <td><?php echo $p['RushingYards']; ?></td>
                        <td><?php echo $p['RushingTouchdowns']; ?></td>
                        <td><?php echo $p['PassingYards']; ?></td>
                        <td><?php echo $p['PassingTouchdowns']; ?></td>
                        <td><?php echo $p['TotalYards']; ?></td>
                        <td><?php echo $p['TotalTDs']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>

            </table>
        </div>

    <?php else: ?>
        <p class="no-results">No results found. Try searching a player or selecting a week.</p>
    <?php endif; ?>

</div>
</body>
</html>
