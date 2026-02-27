<?php
foreach ($__data as $var => $val) {
    $$var = $val; // necessary for blade refactor
}
$users = $tpl->get('users') ?? [];
$departments = $tpl->get('departments') ?? [];
$departmentManagers = $tpl->get('departmentManagers') ?? [];
$customRoles = $tpl->get('customRoles') ?? [];
$smtp = $tpl->get('smtp') ?? [];
$cronCommand = (string) ($tpl->get('cronCommand') ?? '');
?>

<div class="pageheader">
    <div class="pageicon"><span class="fa fa-sitemap"></span></div>
    <div class="pagetitle">
        <h5><?= $tpl->__('label.administration') ?></h5>
        <h1>Organization Roles & Departments</h1>
    </div>
</div>

<div class="maincontent">
    <?= $tpl->displayNotification(); ?>
    <div class="maincontentinner">
        <form method="post" action="<?= BASE_URL ?>/setting/organizationRoles" class="stdform">
            <h4 class="widgettitle title-light"><span class="fa fa-building"></span> Departments & Managers</h4>
            <p>One person can have one role only. Department managers can view assignments for their departments.</p>
            <div class="row">
                <div class="col-md-4">
                    <label for="departments">Departments (one per line)</label>
                    <textarea id="departments" name="departments" rows="8" style="width:100%;"><?= $tpl->escape(implode("\n", $departments)) ?></textarea>
                </div>
                <div class="col-md-8">
                    <label>Department Manager Mapping</label>
                    <?php if (count($departments) === 0) { ?>
                        <p>No departments yet. Add department names first.</p>
                    <?php } else { ?>
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Department</th>
                                <th>Manager</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($departments as $department) { ?>
                                <tr>
                                    <td><?= $tpl->escape($department) ?></td>
                                    <td>
                                        <select name="managerByDepartment[<?= $tpl->escape($department) ?>]">
                                            <option value="0">-- Not assigned --</option>
                                            <?php foreach ($users as $user) { ?>
                                                <option value="<?= (int) $user['id'] ?>" <?= ((int) ($departmentManagers[$department] ?? 0) === (int) $user['id']) ? 'selected="selected"' : '' ?>>
                                                    <?= $tpl->escape(($user['firstname'] ?: $user['username']).' '.$user['lastname'].' ('.$user['username'].')') ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    <?php } ?>
                </div>
            </div>

            <h4 class="widgettitle title-light"><span class="fa fa-id-badge"></span> Role Catalog (Future)</h4>
            <p>Define future roles as <code>code|display name|level</code> one per line. Example: <code>dept_manager|Department Manager|35</code></p>
            <textarea name="customRolesText" rows="6" style="width:100%;"><?php
                $lines = [];
                foreach ($customRoles as $role) {
                    $lines[] = ($role['code'] ?? '').'|'.($role['name'] ?? '').'|'.(int) ($role['level'] ?? 0);
                }
                echo $tpl->escape(implode("\n", $lines));
            ?></textarea>

            <h4 class="widgettitle title-light"><span class="fa fa-envelope"></span> SMTP Credentials (Encrypted in DB)</h4>
            <input type="hidden" name="saveSmtpSettings" value="1" />
            <div class="row">
                <div class="col-md-3"><label><input type="checkbox" name="smtpEnabled" value="1" <?= ! empty($smtp['enabled']) ? 'checked="checked"' : '' ?> /> Enable SMTP</label></div>
                <div class="col-md-3"><label><input type="checkbox" name="smtpAuth" value="1" <?= ! empty($smtp['auth']) ? 'checked="checked"' : '' ?> /> SMTP Auth</label></div>
                <div class="col-md-3"><label><input type="checkbox" name="smtpAutoTls" value="1" <?= ! empty($smtp['autoTls']) ? 'checked="checked"' : '' ?> /> Auto TLS</label></div>
            </div>
            <div class="row">
                <div class="col-md-4"><label>Host</label><input type="text" name="smtpHost" value="<?= $tpl->escape((string) ($smtp['host'] ?? '')) ?>" /></div>
                <div class="col-md-2"><label>Port</label><input type="number" name="smtpPort" value="<?= (int) ($smtp['port'] ?? 587) ?>" /></div>
                <div class="col-md-2"><label>Secure</label><input type="text" name="smtpSecure" value="<?= $tpl->escape((string) ($smtp['secure'] ?? '')) ?>" placeholder="tls / ssl" /></div>
                <div class="col-md-4"><label>From Email</label><input type="email" name="smtpFromEmail" value="<?= $tpl->escape((string) ($smtp['fromEmail'] ?? '')) ?>" /></div>
            </div>
            <div class="row">
                <div class="col-md-6"><label>SMTP Username</label><input type="text" name="smtpUsername" value="<?= $tpl->escape((string) ($smtp['username'] ?? '')) ?>" /></div>
                <div class="col-md-6"><label>SMTP Password <?= ! empty($smtp['passwordSet']) ? '(saved)' : '' ?></label><input type="password" name="smtpPassword" value="" autocomplete="off" placeholder="Leave empty to keep existing" /></div>
            </div>
            <small>Stored encrypted in DB keys under <code>companysettings.smtp.*</code>.</small>

            <h4 class="widgettitle title-light"><span class="fa fa-clock"></span> Cron Expression</h4>
            <p>Copy this to Hostinger cron jobs:</p>
            <input type="text" readonly="readonly" onclick="this.select();" value="<?= $tpl->escape($cronCommand) ?>" style="width:100%;" />

            <br /><br />
            <button type="submit" class="btn btn-primary">Save Organization Settings</button>
        </form>
    </div>
</div>
