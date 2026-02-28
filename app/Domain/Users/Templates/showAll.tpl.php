<?php
defined('RESTRICTED') or exit('Restricted access');
foreach ($__data as $var => $val) {
    $$var = $val; // necessary for blade refactor
}
$roles = $tpl->get('roles');
$orgRoles = $tpl->get('orgRoles') ?? [];
$orgUnits = $tpl->get('orgUnits') ?? [];
$orgUsers = $tpl->get('allUsers') ?? [];
$orgClients = $tpl->get('orgClients') ?? [];
$orgUserRoleMap = $tpl->get('orgUserRoleMap') ?? [];
$orgUserClientMap = $tpl->get('orgUserClientMap') ?? [];
$orgUserUnitMap = $tpl->get('orgUserUnitMap') ?? [];
$orgRoleUsageCounts = $tpl->get('orgRoleUsageCounts') ?? [];
$orgUnitUsageCounts = $tpl->get('orgUnitUsageCounts') ?? [];
$orgRoleNamesByUser = $tpl->get('orgRoleNamesByUser') ?? [];
?>

<div class="pageheader">

    <div class="pageicon"><span class="fa <?php echo $tpl->getModulePicture() ?>"></span></div>
    <div class="pagetitle">
        <h5><?php echo $tpl->__('label.administration') ?></h5>
        <h1><?php echo $tpl->__('headlines.users'); ?></h1>
    </div>
</div><!--pageheader-->

<div class="maincontent">
    <div class="maincontentinner">

        <?php echo $tpl->displayNotification() ?>

        <div class="row">
            <div class="col-md-6">
                <a href="<?= BASE_URL ?>/users/newUser" class="btn btn-primary userEditModal"><i class='fa fa-plus'></i> <?= $tpl->__('buttons.add_user') ?> </a>
            </div>
            <div class="col-md-6 align-right">

            </div>
        </div>

        <table class="table table-bordered" id="allUsersTable">
            <colgroup>
                <col class="con1">
                <col class="con0">
                <col class="con1">
                <col class="con0">
                <col class="con1">
                <col class="con0">
                <col class="con1">
            </colgroup>
            <thead>
                <tr>
                    <th class='head1'><?php echo $tpl->__('label.name'); ?></th>
                    <th class='head0'><?php echo $tpl->__('label.email'); ?></th>
                    <th class='head1'><?php echo $tpl->__('label.client'); ?></th>
                    <th class='head1'><?php echo $tpl->__('label.role'); ?></th>
                    <th class='head1'><?php echo $tpl->__('label.status'); ?></th>
                    <th class='head1'><?php echo $tpl->__('headlines.twoFA'); ?></th>
                    <th class='head0 no-sort'></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tpl->get('allUsers') as $row) { ?>
                    <tr>
                        <td style="padding:6px 10px;">
                             <a href="<?= BASE_URL ?>/users/editUser/<?= $row['id']?>"><?= sprintf($tpl->__('text.full_name'), $tpl->escape($row['firstname']), $tpl->escape($row['lastname'])); ?></a>
                        </td>
                        <td><a href="<?= BASE_URL ?>/users/editUser/<?= $row['id']?>"><?= $tpl->escape($row['username']); ?></a></td>
                        <td><?= $tpl->escape($row['clientName']); ?></td>
                        <td>
                            <?php
                                $businessRole = $orgRoleNamesByUser[(int) $row['id']] ?? '';
                                if ($businessRole !== '') {
                                    echo $tpl->escape($businessRole);
                                } else {
                                    echo $tpl->__('label.roles.'.$roles[$row['role']]);
                                }
                            ?>
                        </td>
                        <td><?php if (strtolower($row['status']) == 'a') {
                            echo $tpl->__('label.active');
                        } elseif (strtolower($row['status']) == 'i') {
                            echo $tpl->__('label.invited');
                        } else {
                            echo $tpl->__('label.deactivated');
                        } ?></td>
                        <td><?php if ($row['twoFAEnabled']) {
                            echo $tpl->__('label.yes');
                        } else {
                            echo $tpl->__('label.no');
                        } ?></td>
                        <td><a href="<?= BASE_URL ?>/users/delUser/<?php echo $row['id']?>" class="delete"><i class="fa fa-trash"></i> <?= $tpl->__('links.delete'); ?></a></td>
                    </tr>
            <?php } ?>
            </tbody>
        </table>

        <div id="rbacUnitManagement" style="margin-top:20px;">
            <h4 class="widgettitle title-light"><span class="fa fa-sitemap"></span> Unit and Role Management</h4>
            <div class="row">
                <div class="col-md-6">
                    <h5 class="widgettitle title-light"><span class="fa fa-diagram-project"></span> Units</h5>
                    <form method="post" action="<?= BASE_URL ?>/users/showAll#rbacUnitManagement" style="margin-bottom:10px;">
                        <input type="hidden" name="addUnit" value="1" />
                        <input type="text" name="unitName" placeholder="Add unit name" style="width:70%;" />
                        <button type="submit" class="btn btn-primary">Add Unit</button>
                    </form>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Unit</th>
                                <th>Mapped Users</th>
                                <th style="width:120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orgUnits as $unit) { ?>
                                <?php $uid = (int) ($unit['id'] ?? 0); ?>
                                <tr>
                                    <td><?= $tpl->escape((string) ($unit['name'] ?? '')) ?></td>
                                    <td><?= (int) ($orgUnitUsageCounts[$uid] ?? 0) ?></td>
                                    <td>
                                        <form method="post" action="<?= BASE_URL ?>/users/showAll#rbacUnitManagement" onsubmit="return confirm('Delete this unit?');">
                                            <input type="hidden" name="deleteUnit" value="1" />
                                            <input type="hidden" name="unitId" value="<?= $uid ?>" />
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5 class="widgettitle title-light"><span class="fa fa-user-shield"></span> Global Roles</h5>
                    <form method="post" action="<?= BASE_URL ?>/users/showAll#rbacUnitManagement" style="margin-bottom:10px;">
                        <input type="hidden" name="addRole" value="1" />
                        <input type="text" name="roleName" placeholder="Add role name" style="width:45%;" />
                        <select name="roleSystemRole" style="width:30%;">
                            <option value="5">ReadOnly (5)</option>
                            <option value="10">Commentor (10)</option>
                            <option value="20" selected="selected">Editor (20)</option>
                            <option value="30">Manager (30)</option>
                            <option value="40">Admin (40)</option>
                            <option value="50">Owner (50)</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Add Role</button>
                    </form>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>System Level</th>
                                <th>Mapped Users</th>
                                <th style="width:120px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orgRoles as $role) { ?>
                                <?php $rid = (int) ($role['id'] ?? 0); ?>
                                <tr>
                                    <td><?= $tpl->escape((string) ($role['name'] ?? '')) ?></td>
                                    <td><?= (int) ($role['systemRole'] ?? 0) ?></td>
                                    <td><?= (int) ($orgRoleUsageCounts[$rid] ?? 0) ?></td>
                                    <td>
                                        <?php if ((int) ($role['isProtected'] ?? 0) === 1) { ?>
                                            <span class="label label-info">Protected</span>
                                        <?php } else { ?>
                                            <form method="post" action="<?= BASE_URL ?>/users/showAll#rbacUnitManagement" onsubmit="return confirm('Delete this role?');">
                                                <input type="hidden" name="deleteRole" value="1" />
                                                <input type="hidden" name="roleId" value="<?= $rid ?>" />
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h5 class="widgettitle title-light"><span class="fa fa-users-gear"></span> User Role and Client Mapping</h5>
                    <form method="post" action="<?= BASE_URL ?>/users/showAll#rbacUnitManagement">
                        <input type="hidden" name="saveUserMappings" value="1" />
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Business Role</th>
                                    <th>Units</th>
                                    <th>Clients</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orgUsers as $user) { ?>
                                    <?php
                                        $userId = (int) ($user['id'] ?? 0);
                                        $mappedRoleId = (int) ($orgUserRoleMap[$userId] ?? 0);
                                        $mappedUnitIds = $orgUserUnitMap[$userId] ?? [];
                                        $mappedClientIds = $orgUserClientMap[$userId] ?? [];
                                    ?>
                                    <tr>
                                        <td><?= $tpl->escape((string) (($user['firstname'] ?? '').' '.($user['lastname'] ?? '').' <'.($user['username'] ?? '').'>')) ?></td>
                                        <td>
                                            <select name="userBusinessRole[<?= $userId ?>]" style="width:220px;">
                                                <option value="">-- Select role --</option>
                                                <?php foreach ($orgRoles as $role) { ?>
                                                    <?php $roleId = (int) ($role['id'] ?? 0); ?>
                                                    <option value="<?= $roleId ?>" <?= $mappedRoleId === $roleId ? 'selected="selected"' : '' ?>>
                                                        <?= $tpl->escape((string) ($role['name'] ?? '')) ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="userUnits[<?= $userId ?>][]" multiple="multiple" style="width:240px;">
                                                <?php foreach ($orgUnits as $unit) { ?>
                                                    <?php $unitId = (int) ($unit['id'] ?? 0); ?>
                                                    <option value="<?= $unitId ?>" <?= in_array($unitId, $mappedUnitIds, true) ? 'selected="selected"' : '' ?>>
                                                        <?= $tpl->escape((string) ($unit['name'] ?? '')) ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="userClients[<?= $userId ?>][]" multiple="multiple" style="width:240px;">
                                                <?php foreach ($orgClients as $client) { ?>
                                                    <?php $clientId = (int) ($client['id'] ?? 0); ?>
                                                    <option value="<?= $clientId ?>" <?= in_array($clientId, $mappedClientIds, true) ? 'selected="selected"' : '' ?>>
                                                        <?= $tpl->escape((string) ($client['name'] ?? '')) ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                        <button type="submit" class="btn btn-primary">Save Mappings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function() {
            leantime.usersController.initUserTable();
            leantime.usersController._initModals();
            leantime.usersController.initUserEditModal();

        }
    );

</script>
