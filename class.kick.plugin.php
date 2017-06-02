<?php
$PluginInfo['kick'] = [
    'Name' => 'Kick',
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
            'Kick',
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
     * @param ProfileController $sender Instance of the calling object.
     *
     * @return void.
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.Kick'] = t('Notify me when someone kicked my ass.');
        $sender->Preferences['Notifications']['Popup.Kick'] = t('Notify me when someone kicked my ass');
    }

    /**
     * Add button to profile.
     *
     * @param ProfileController $sender Instance of the calling object.
     *
     * @return void.
     */
   public function profileController_beforeProfileOptions_handler($sender) {
        $sessionUserID = Gdn::session()->UserID;
        $profileUserID = $sender->User->UserID;
        if ($profileUserID == $sessionUserID || $sessionUserID < 1) {
            // Exit if this is visitors own profile or visitor is guest.
            return;
        }

        // echo '<div class="KickWrapper">';
        echo anchor(
            trim(sprite('SpKick').' '.t('Kick')),
            '/plugin/kick/'.$sender->User->UserID.'?tk='.Gdn::session()->transientKey(),
            [
                'class' => 'Button NavButton KickButton Hijack',
                // 'onClick' => 'gdn.inform("kicked");'
            ]
        );
        // echo '</div>';
        /*
        $sender->EventArguments['MemberOptions'][] = [
            'Text' => sprite('SpKick').' '.t('Kick'),
            'Url' => '/plugin/kick/'.$sender->User->UserID.'&tk='.Gdn::session()->transientKey(),
            'CssClass' => 'KickUser Hijack'
        ];
        */
    }

    public function pluginController_kick_create($sender) {
        if (!Gdn::session()->validateTransientKey(Gdn::request()->get('tk', false))) {
            throw permissionException();
            return;
        }
        $profileUserID = val(0, $sender->RequestArgs, 0);
        if ($profileUserID < 1) {
            throw notFoundException('User');
        }

        $activityModel = new activityModel();
        //  public function add($ActivityUserID, $ActivityType, $Story = null, $RegardingUserID = null, $CommentActivityID = null, $Route = null, $SendEmail = '')


        $activityModel->add(
            Gdn::session()->UserID, // ActivityUserID
            'Kick', // ActivityType
            null, // Story
            profileUserID, // RegardingUserID
            null, // CommentActivityID
            'profile', // 'link target', // Route
            '' // SendEmail
        );

        echo json_encode(['InformMessages' => t("You've kicked ass!")]);
    }
}
