<?php
declare(strict_types=1);
$page_title = 'Contacts';
$current_page = 'contacts';
require_once __DIR__ . '/includes/contacts_loader.php';
$contacts = load_contacts_from_files();
$contactsError = ''; // optional: set if you want to show a read error (e.g. dir missing is handled by empty list)
require __DIR__ . '/includes/header.php';
?>
<h1>Contacts</h1>
<p class="contacts-intro">Reach the right team at Pass &amp; Play. All contacts are maintained in our contact directory.</p>

<?php if (!empty($contactsError)): ?>
    <p class="error"><?= htmlspecialchars($contactsError) ?></p>
<?php endif; ?>

<?php if (empty($contacts)): ?>
    <div class="contacts-empty">
        <p>No contacts are available at the moment. Please check back later or ensure the contact data file is in place.</p>
    </div>
<?php else: ?>
    <div class="contacts-table-wrap">
        <table class="contacts-table" role="grid">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Role</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Discord</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $c): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['role']) ?></td>
                        <td><a href="mailto:<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></a></td>
                        <td><?= htmlspecialchars($c['phone']) ?></td>
                        <td><?= htmlspecialchars($c['discord']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<section class="section contact-form-box">
    <h3>Send us a message <span class="coming-soon">Coming soon</span></h3>
    <p>We’re adding a contact form so you can reach us directly from this page. For now, please use the email addresses above.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
