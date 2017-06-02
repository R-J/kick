<?php
$PluginInfo['kickAss'] = [
    'Name' => 'Kick Ass',
    'Description' => 'Adds a button to users profiles which when clicked sends a short notification to the profile user.',
    'Version' => '0.0.1',
    'RequiredApplications' => ['Vanilla' => '>= 2.3'],
    'SettingsPermission' => 'Garden.Settings.Manage',
    'MobileFriendly' => true,
    'HasLocale' => true,
    'Author' => 'Robin Jurinka',
    'AuthorUrl' => 'http://vanillaforums.org/profile/r_j',
    'License' => 'MIT'
];

class KickAssPlugin extends Gdn_Plugin {
    public function setup() {
        $this->structure();
    }

    /**
     * Add activity type.
     *
     * @return void.
     */
    public function structure() {
        $activityModel = new ActivityModel();
        $activityModel->defineType(
            'KickAss',
            [
                'AllowComments' => false,
                'ShowIcon' => true,
                // %1 = ActivityName ( = acting users name)
                // %2 = ActivityName Possessive
                // %3 = RegardingName ( = affected users name)
                // %4 = RegardingName Possessive
                // %5 = Link to RegardingName's Wall
                // %6 = his/her
                // %7 = he/she
                // %8 = RouteCode & Route
                'ProfileHeadline' => '%1$s kicked your ass.',
                'FullHeadline' => '%1$s kicked your ass.',
                'RouteCode' => 'profile',
                'Notify' => '1',
                'Public' => '0'
            ]
        );
    }

    /**
     * Add notification options for users.
     *
     * @param ProfileCOntroller $sender Instance of the calling object.
     *
     * @return void.
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.KickAss'] = t('Notify me when someone kicked my ass.');
        $sender->Preferences['Notifications']['Popup.KickAss'] = t('Notify me when someone kicked my ass');
    }

    public function profileController_beforeInfo_create($sender, $args) {
        echo '<h3>'.__METHOD__.'</h3>';
    }
    public function profileController_onBasicInfo_create($sender, $args) {
        echo '<h3>'.__METHOD__.'</h3>';
    }
    public function profileController_afterInfo_create($sender, $args) {
        echo '<h3>'.__METHOD__.'</h3>';
    }
    public function profileController_UsernameMeta_create($sender, $args) {
        echo '<h3>'.__METHOD__.'</h3>';
    }
    public function profileController_BeforeUserInfo_create($sender, $args) {
        echo '<h3>'.__METHOD__.'</h3>';
    }
    public function profileController_AfterUserInfo_create($sender, $args) {
        echo '<h3>'.__METHOD__.'</h3>';
    }
}
