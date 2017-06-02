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
         // Default notification values. These values will only apply for users
         // who haven't configured their notificaitions yet.
        touchConfig('kick.UseDropDownButton', false);
        touchConfig('Preferences.Popup.Kick', 1);
        touchConfig('Preferences.Email.Kick', 0);
        // Init db changes.
        $this->structure();
    }

    /**
     * Add activity type.
     *
     * @return void.
     */
    public function structure() {
        // Create a new activity.
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

        // Loop through all users and add notification setting if needed.
        // TODO!
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

        // Exit if this is the visitors own profile or visitor is guest.
        if ($profileUserID == $sessionUserID || $sessionUserID < 1) {
            return;
        }

        $text = trim(sprite('SpKick').' '.t('Kick'));
        $url = '/plugin/kick/'.$sender->User->UserID.'&tk='.Gdn::session()->transientKey();

        if (c('kick.UseDropDownButton', false)) {
            // Enhance messge button on profile with a second option
            $sender->EventArguments['MemberOptions'][] = [
                'Text' => $text,
                'Url' => $url,
                'CssClass' => 'KickButton Hijack'
            ];
        } else {
            // Add some styling.
            echo '<style>.KickButton{margin-right:4px}</style>';
            // Show button on profile.
            echo anchor(
                $text,
                $url,
                [
                    'class' => 'NavButton KickButton Hijack',
                    // 'onClick' => 'gdn.inform("kicked");'
                ]
            );
        }




        /*
         * This could be used if you would like to have a kick/message dropdown
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

        $userModel = new UserModel();
        $profileUser = $userModel->getID($profileUserID);

        $activityModel = new activityModel();
        $activityID = $activityModel->add(
            Gdn::session()->UserID, // ActivityUserID
            'Kick', // ActivityType
            'story dynamic.', // Story
            $profileUserID, // RegardingUserID
            '', // CommentActivityID
            userUrl($profileUser), // Route
            '' // SendEmail
        );

        echo json_encode(['InformMessages' => t("You've kicked ass!")]);
    }
}
