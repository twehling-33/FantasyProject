
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Home">
    <title>Home • Fantasy Futures - Fantasy Football Analytics</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="header">
    <div class="container">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem;">
            <a href="index.php" style="font-weight:800; font-size:1.8rem;">Fantasy Futures - Fantasy Football Analytics</a>
            <nav aria-label="Main">
                <ul>
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="/pages/playersearch.php">Player Search</a></li>
                    <li><a href="/pages/trade_analyzer.php">Fantasy Football Trade Calculator</a></li>
                    <li><a href="/pages/startsit.html">Start/Sit Analysis</a></li>
                    <li><a href="/pages/about.html">About</a></li>
                    <li><a href="/pages/contact.html">Contact</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<main class="container">

    <!-- Hero / Intro -->
    <section class="hero card">
        <h1>Welcome to Fantasy Futures</h1>
        <p>
            This site is a fantasy football analytics toolbox.
            We use real 2024 NFL weekly stats to power tools like player search, trade analysis, and start/sit help.
        </p>
    </section>

    <section class="grid">
        <div class="card">
            <h2>Player Stats Lookup</h2>
            <p>
                Search weekly performance for any skill-position player.
                Filter by week, position, and team to quickly find the numbers you need.
            </p>
            <p><a href="playersearch.php">Go to Player Search →</a></p>
        </div>

        <div class="card">
            <h2>Trade Analyzer (PPR)</h2>
            <p>
                Compare two sides of a trade using PPR scoring based on actual production.
                Enter player names or IDs, pick a week range, and see which side wins.
            </p>
            <p><a href="trade_analyzer.php">Go to Trade Analyzer →</a></p>
        </div>

        <div class="card">
            <h2>Start/Sit Analysis</h2>
            <p>
                Use our start/sit page to help decide between players based on matchups
                and their historical performance from our database.
            </p>
            <p><a href="startsit.php">Go to Start/Sit →</a></p>
        </div>
    </section>

    <section class="card">
        <h2>Top Fantasy Quarterbacks (Video)</h2>
        <p>
            Check out this breakdown of the best current fantasy quarterbacks by an upcoming creator for additional insight:
        </p>

        <div class="embed">
            <div class="ratio-16x9">
                <iframe
                        src="https://www.youtube-nocookie.com/embed/J_v1D7444oA?modestbranding=1&rel=0"
                        title="Best Current Fantasy Quarterbacks"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen
                        loading="lazy">
                </iframe>
            </div>
        </div>
    </section>

    <!-- About the project blurb -->
    <section class="card">
        <h2>About This Project</h2>
        <p>
            Fantasy Futures was built to help players use historical trends to predict their players upcoming performance.
            All of the data-driven tools on this site are built from a 2024 NFL stats database.
        </p>
        <p>
            To learn more about the technologies and decisions behind the project,
            visit the <a href="about.html">About page →</a>
        </p>
    </section>

    <div class="footer">
        © <span id="year"></span> Fantasy Futures · Built with HTML, CSS &amp; PHP
    </div>
</main>

<script>
    document.getElementById('year').textContent = new Date().getFullYear();
</script>
</body>
</html>
