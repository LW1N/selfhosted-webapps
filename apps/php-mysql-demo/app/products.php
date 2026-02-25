<?php
declare(strict_types=1);
$page_title = 'Products &amp; Services';
$current_page = 'products';
require __DIR__ . '/includes/header.php';
?>
<h1>Products &amp; Services</h1>

<section class="section">
    <h2>Plans &amp; pricing</h2>
    <div class="contacts-table-wrap">
        <table class="pricing-table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Best for</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Free</strong></td>
                    <td>Small groups, trying Pass &amp; Play</td>
                    <td>$0 / month</td>
                </tr>
                <tr>
                    <td><strong>Pro</strong></td>
                    <td>Growing communities, creators, teams</td>
                    <td>$9 / month</td>
                </tr>
                <tr>
                    <td><strong>Community+</strong></td>
                    <td>Large servers, events, custom branding</td>
                    <td>Contact sales</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="section">
    <h2>Who it’s for</h2>
    <div class="card-grid">
        <div class="card">
            <h3>For creators</h3>
            <p>Run your community with one place for chat, voice, and events. Custom roles and moderation tools included.</p>
        </div>
        <div class="card">
            <h3>For teams</h3>
            <p>Keep work and play in sync. Threaded discussions, scheduled meetings, and simple permissions.</p>
        </div>
        <div class="card">
            <h3>For gamers</h3>
            <p>Low-latency voice, game nights, and LFG channels. Lightweight so it doesn’t get in the way.</p>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
