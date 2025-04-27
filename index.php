<?php
$db = new PDO('sqlite:mweblist.db');

$stmt = $db->query("
    SELECT ROUND(amount, 1) as amount, COUNT(*) as count
    FROM mweb_pegins
    GROUP BY ROUND(amount, 1)
    ORDER BY count DESC, amount ASC
");
$standardizedPegins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("
    SELECT amount, COUNT(*) as count
    FROM mweb_pegins
    GROUP BY amount
    ORDER BY amount DESC
");
$randomPegins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT mweb_total FROM mweb_total");
$mwebTotal = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT last_scanned_block FROM scan_progress");
$syncHeight = $stmt->fetch(PDO::FETCH_ASSOC);

$mwebTotalValue = $mwebTotal['mweb_total'] ?? 'N/A';
$syncHeightValue = $syncHeight['last_scanned_block'] ?? 'N/A';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#5271ff">
        <link rel="canonical" href="https://mweblist.com"/>
        <meta name="robots" content="index, follow">
        <meta name="description" content="A list of MWEB peg-ins to help with obfuscation."/>
        <meta name="author" content="Tech1k">
        <title>MWEB List - Common Peg-In Amounts for Obfuscation</title>
        <link rel="shortcut icon" href="/assets/favicon.png"/>
        <meta property="og:title" content="LibreNode - Home"/>
        <meta property="og:description" content="A list of MWEB peg-ins to help with obfuscation."/>
        <meta property="og:type" content="website"/>
        <meta property="og:url" content="https://mweblist.com"/>
        <meta property="og:site_name" content="MWEB List"/>
        <link rel="stylesheet" href="/assets/style.css">
    </head>
    <body>
        <div id="main" style="text-align: center;">
            <h1>MWEB Peg-In List</h1>
            <h3>A list of MWEB peg-ins ordered by occurrence since activation. By using common peg-in amounts, you can blend in with other users and increase your obfuscation when entering MWEB. <a href="#faq">Learn more in the FAQs</a>.</h3>
            <h3>Total LTC in MWEB as of block <?php echo number_format(htmlspecialchars($syncHeightValue)); ?>: <?php echo htmlspecialchars($mwebTotalValue); ?> LTC</h3>
            <h3><i>Note: The database is still syncing and updates periodically. Amounts displayed will be incorrect until completely synced.</i></h3>
        </div>
        <div class="filters">
            <label for="minAmount">Min LTC:</label>
            <input type="number" id="minAmount" step="0.01" min="0">
            <label for="maxAmount">Max LTC:</label>
            <input type="number" id="maxAmount" step="0.01" min="0">
            <label for="minOccurrences">Min Occurrences:</label>
            <input type="number" id="minOccurrences" min="1">
            <!--<label for="search">Search LTC:</label>
            <input type="text" id="search" placeholder="Search...">-->
        </div>
        <h2 class="section-title">Common (Rounded) Peg-In Amounts</h2>
        <table id="standardizedTable">
            <thead>
                <tr>
                    <th scope="col">Amount (LTC)</th>
                    <th scope="col">Occurrences</th>
                </tr>
            </thead>
            <tbody id="standardizedMainBody">
                <?php foreach ($standardizedPegins as $row): ?>
                    <?php if ($row['count'] > 5 && $row['amount'] != 0.0): ?>
                        <tr>
                            <td class="amount"><?php echo htmlspecialchars(number_format($row['amount'], 1)); ?></td>
                            <td class="count"><?php echo htmlspecialchars($row['count']); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h3 class="section-title">
            <button id="toggleRareStandardized" class="toggle-button">➕ Show Low Occurrence (≤5 count) Peg-Ins</button>
        </h3>
        <div id="rareStandardizedContainer" style="display: none;">
            <table id="rareStandardizedTable">
                <thead>
                    <tr>
                        <th>Amount (LTC)</th>
                        <th>Occurrences</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($standardizedPegins as $row): ?>
                        <?php if ($row['count'] < 6): ?>
                            <tr>
                                <td class="amount"><?php echo htmlspecialchars(number_format($row['amount'], 1)); ?></td>
                                <td class="count"><?php echo htmlspecialchars($row['count']); ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <h2 class="section-title">
            <button id="toggleRandomTable" class="toggle-button">➕ Show Unique (non-rounded) Peg-Ins</button>
        </h2>
        <div id="randomTableContainer" style="display: none;">
            <table id="randomTable">
                <thead>
                    <tr>
                        <th>Amount (LTC)</th>
                        <th>Occurrences</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($randomPegins as $row): ?>
                        <tr>
                            <td class="amount"><?php echo htmlspecialchars(number_format($row['amount'], 8)); ?></td>
                            <td class="count"><?php echo htmlspecialchars($row['count']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <section id="faq">
            <div id="main">
                <h2>FAQs</h2>
                <h3>What is this site?</h3>
                <p>This site displays commonly used peg-in amounts for Litecoin's MimbleWimble Extension Block (MWEB). Peg-ins are public events on the Litecoin blockchain where coins are moved into the private MWEB sidechain.</p>
                <h3>What is MWEB?</h3>
                <p>MWEB (MimbleWimble Extension Block) is an optional privacy and scalability upgrade for Litecoin. It allows users to send and receive confidential transactions by moving coins into a separate sidechain within the Litecoin blockchain where amounts and addresses are hidden from public view.</p>
                <p>To learn more about MWEB, check out the <a href="https://litecoin.com/learning-center/litecoin-and-mweb-what-it-is-and-how-to-use-it" target="_blank" rel="noopener noreferrer">official MWEB overview</a>.</p>
                <h3>How does using a common amount increase obfuscation?</h3>
                <p>Using common peg-in amounts makes it harder for outside observers to link specific peg-ins to individual users. If everyone uses similar amounts when moving funds into MWEB, it becomes more difficult to distinguish between different transactions, improving the overall obfuscation of the network.</p>
                <h3>You mention obfuscation a lot, what about increasing privacy?</h3>
                <p>While obfuscation is a part of privacy, it's not everything. To increase your privacy before pegging-in, you should use a new address and receive coins not linked to you on the public chain. When you want to move back to the main chain, you should move your coins in MWEB at least once to "mix" your coins before pegging-out to increase your privacy and if possible, not peg-out to the same address your used to peg-in with.</p>
                <h3>I thought MWEB was private — how can you see these peg-in amounts?</h3>
                <p>While transactions inside MWEB are private, the act of pegging coins into MWEB happens on the regular Litecoin blockchain and is visible. The peg-in transaction itself shows the amount being transferred into MWEB, even though the subsequent private transactions are not visible. This site only tracks those public peg-in events, not what happens once the coins are inside MWEB.</p>
                <h3>Can I peg-out back to regular Litecoin?</h3>
                <p>Yes! You can peg-out from MWEB back to the regular Litecoin main chain at any time. When you peg-out, the transaction amount and recipient address are again visible on the public blockchain, but your activity while inside MWEB remains private.</p>
                <h3>Is there a fee for pegging into or out of MWEB?</h3>
                <p>Yes, just like any Litecoin transaction, peg-in and peg-out transactions require a standard network fee to be processed by miners. The fee is usually small, but it depends on network conditions and the size of your transaction.</p>
                <h3>How can I support this site?</h3>
                <p>
                    <strong>LTC Address:</strong><br/>
                    ltc1q49qxt7zllrzzejvu8sy344vtnfw4a5yk6rjndk
                </p>
                <p style="word-break: break-word;">
                    <strong>MWEB Address:</strong><br/>
                    ltcmweb1qqwc4tkjwck8ecqgs6d63p3j8sx23qmpke56yy5596tw3h924uvsewq7tv3h2dhlfeunqxcl75rpdqtr4h0tth3kncc2ttwysuz83g889ccl8ryxs
                </p>
                <p><strong>OpenAlias: </strong>tech1k.com</p>
            </div>
        </section>
        <br/>
        <hr/>
        <footer>
            <p style="text-align: center; font-size: 0.9em; color: gray;">
                Disclaimer: This site is provided "as is" with no warranties. Use at your own risk. Always do your own research and verification.
            </p>
            <p style="text-align: center;">Made with ♥️ and ☕ by <a href="https://tech1k.com" target="_blank" rel="noopener">Tech1k</a>. This site is <a href="https://github.com/Tech1k/mweblist.com" target="_blank" rel="noopener">open-source</a>.</p>
        </footer>
        <script>
            const standardizedTableRows = document.querySelectorAll("#standardizedTable tbody tr");
            const randomTableRows = document.querySelectorAll("#randomTable tbody tr");

            const minAmountInput = document.getElementById("minAmount");
            const maxAmountInput = document.getElementById("maxAmount");
            const minOccurrencesInput = document.getElementById("minOccurrences");
            // const searchInput = document.getElementById("search");

            function filterTables() {
                const minAmount = parseFloat(minAmountInput.value) || 0;
                const maxAmount = parseFloat(maxAmountInput.value) || Infinity;
                const minOccurrences = parseInt(minOccurrencesInput.value) || 1;
                // const searchTerm = searchInput.value.toLowerCase(); // Commented out

                standardizedTableRows.forEach(row => {
                    const amount = parseFloat(row.querySelector(".amount").textContent);
                    const count = parseInt(row.querySelector(".count").textContent);

                    const matchesAmount = amount >= minAmount && amount <= maxAmount;
                    const matchesOccurrences = count >= minOccurrences;
                    // const matchesSearch = row.querySelector(".amount").textContent.toLowerCase().includes(searchTerm); // Commented out

                    // Only apply the filtering based on amount and occurrences
                    if (matchesAmount && matchesOccurrences /* && matchesSearch */) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });

                randomTableRows.forEach(row => {
                    const amount = parseFloat(row.querySelector(".amount").textContent);
                    const count = parseInt(row.querySelector(".count").textContent);

                    const matchesAmount = amount >= minAmount && amount <= maxAmount;
                    const matchesOccurrences = count >= minOccurrences;
                    // const matchesSearch = row.querySelector(".amount").textContent.toLowerCase().includes(searchTerm); // Commented out

                    // Only apply the filtering based on amount and occurrences
                    if (matchesAmount && matchesOccurrences /* && matchesSearch */) {
                        row.style.display = "";
                    } else {
                        row.style.display = "none";
                    }
                });
            }

            minAmountInput.addEventListener("input", filterTables);
            maxAmountInput.addEventListener("input", filterTables);
            minOccurrencesInput.addEventListener("input", filterTables);
            // searchInput.addEventListener("input", filterTables); // Commented out

            filterTables();

            const toggleButton = document.getElementById("toggleRandomTable");
            const randomTableContainer = document.getElementById("randomTableContainer");

            toggleButton.addEventListener("click", () => {
                if (randomTableContainer.style.display === "none") {
                    randomTableContainer.style.display = "block";
                    toggleButton.textContent = "➖ Hide Unique (non-rounded) Peg-Ins";
                } else {
                    randomTableContainer.style.display = "none";
                    toggleButton.textContent = "➕ Show Unique (non-rounded) Peg-Ins";
                }
            });

            const toggleRareButton = document.getElementById("toggleRareStandardized");
            const rareStandardizedContainer = document.getElementById("rareStandardizedContainer");

            toggleRareButton.addEventListener("click", () => {
                if (rareStandardizedContainer.style.display === "none") {
                    rareStandardizedContainer.style.display = "block";
                    toggleRareButton.textContent = "➖ Hide Low Occurrence (≤5 count) Peg-Ins";
                } else {
                    rareStandardizedContainer.style.display = "none";
                    toggleRareButton.textContent = "➕ Show Low Occurrence (≤5 count) Peg-Ins";
                }
            });
        </script>
    </body>
</html>
