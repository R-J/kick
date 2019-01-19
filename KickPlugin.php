<?php

class KickAssPlugin extends Gdn_Plugin {
    /** @var string HTML markup of the additional profile options button */
    private $kickButton = '';

    /**
     * Init some sane config defaults on plugin activation and required db changes.
     *
     * @return void.
     */
    public function setup() {
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
    }

    /**
     * Simple settings for choosing a dedicated "Kick Ass" button or a dropdown.
     *
     * @param SettingsController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function settingsController_kick_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->title(t('Kick Settings'));
        $sender->setHighlightRoute('dashboard/settings/plugins');

        $configurationModule = new ConfigurationModule($sender);
        $configurationModule->initialize(
            [
                'kick.UseDropDownButton' => [
                    'Control' => 'CheckBox',
                    'Description' => 'Take influence on how the button is displayed on users profiles.'
                ]
            ]
        );
        $configurationModule->renderAll();
    }


    /**
     * Add notification options for users.
     *
     * @param ProfileController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.Kick'] = t('Notify me when someone kicked me.');
        $sender->Preferences['Notifications']['Popup.Kick'] = t('Notify me when someone kicked me.');
    }

    /**
     * Add button to profile.
     *
     * @param ProfileController $sender Instance of the calling object.
     *
     * @return void.
     */
    public function profileController_beforeProfileOptions_handler($sender, $args) {
        if (!$this->showButton($sender->User)) {
            return;
        }
        $text = trim(sprite('SpKick').' '.t('Kick'));
        $url = '/plugin/kick?id='.$sender->User->UserID.'&tk='.Gdn::session()->transientKey();
        if (Gdn::config('kick.UseDropDownButton', false)) {
            // Enhance message button on profile with a second option.
            $args['MemberOptions'][] = [
                'Text' => $text,
                'Url' => $url,
                'CssClass' => 'KickButton Hijack'
            ];
        } else {
            $this->kickButton = anchor(
                $text,
                $url,
                'NavButton KickButton'
            ).' ';
        }
    }

    /**
     * Change ProfileOptionsModule if needed.
     *
     * @param ProfileController $sender Instance of the calling class.
     *
     * @return void.
     */
    public function profileController_afterAddSideMenu_handler($sender) {
        $module = $sender->Assets['Content']['ProfileOptionsModule'] ?? null;
        // Only proceed if the module is available.
        if (!$module) {
            return;
        }
        // Use custom view for ProfileOptions.
        $module->setView(Gdn::controller()->fetchViewLocation('profileoptions', '', '/plugins/kick'));
        // Add KickButton markup.
        $module->setData('KickButton', $this->kickButton);
    }

    /**
     * Helper function to decide whether a button should be displayed or not.
     *
     * @param Object $profileUser The profile user.
     *
     * @return boolean Whether a button should be shown.
     */
    private function showButton($profileUser) {
        // Exit if this is the visitors own profile or visitor is a guest.
        $sessionUserID = Gdn::session()->UserID;
        if ($profileUser->UserID == $sessionUserID || $sessionUserID < 1) {
            return false;
        }

        // Ensure that button is only shown if user would get a notification.
        $defaultPopup = Gdn::config('Preferences.Popup.Kick', true);
        $defaultEmail = Gdn::config('Preferences.Email.Kick', false);
        $userConfigPopup = $profileUser->Preferences['Popup.Kick'] ?? $defaultPopup;
        $userConfigEmail = $profileUser->Preferences['Email.Kick'] ?? $defaultEmail;
        if ($userConfigPopup == false && $userConfigEmail == false) {
            return false;
        }

        return true;
    }

    /**
     * Send notification to a profile user and gives feedback to visitor.
     *
     * @param PluginController $sender Instance of the calling object.
     *
     * @return string Json encoded status of the action.
     */
    public function pluginController_kick_create($sender) {
        if (!Gdn::session()->validateTransientKey(Gdn::request()->get('tk', false))) {
            throw permissionException();
            return;
        }
        $profileUserID = Gdn::request()->get('id', 0);
        if ($profileUserID < 1) {
            throw notFoundException('User');
        }

        // Needed for getting the users url.
        $userModel = new UserModel();
        $profileUser = $userModel->getID($profileUserID);

        // Create a new activity.
        $activityModel = new activityModel();
        $activityID = $activityModel->add(
            Gdn::session()->UserID, // ActivityUserID
            'Kick', // ActivityType
            '', // Story
            $profileUserID, // RegardingUserID
            '', // CommentActivityID
            userUrl($profileUser), // Route
            '' // SendEmail
        );

        // Give acting user feedback.
        if ($activityID) {
            $message = 'You\'ve kicked %1$s!';
        } else {
            $message = 'Kicking %1$s failed!';
        }
        $feedback = sprintf(
            t($message),
            htmlspecialchars($profileUser->Name)
        );
        echo json_encode(['InformMessages' =>  [
            [
                'Message' => $feedback,
                'CssClass' => 'Dismissable AutoDismiss',
            ]
        ]]);
    }
}
