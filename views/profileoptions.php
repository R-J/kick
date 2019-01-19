<?php if (!defined('APPLICATION')) exit();
/** Displays the "Edit My Profile" or "Back to Profile" buttons on the top of the profile page. */
?>
<div class="ProfileOptions">
    <?php
    if (Gdn::controller()->EditMode)  {
        echo anchor(t('Back to Profile'), userUrl(Gdn::controller()->User), ['class' => 'ProfileButtons']);
    } else {
        echo $this->data('ProfileOptionsDropdown').' ';
        echo buttonGroup($this->data('MemberOptions'), 'NavButton MemberButtons');
    }
    ?>
</div>
