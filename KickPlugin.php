<?php

class KickAssPlugin extends Gdn_Plugin {
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

    public function settingsController_kick_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $sender->title(t('Kick Settings'));
        $sender->addSideMenu('dashboard/settings/plugins');

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
     * @param ProfileController $sender Instance of the calling object.
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
        $sessionUserID = Gdn::session()->UserID;
        $profileUserID = $sender->User->UserID;

        // Exit if this is the visitors own profile or visitor is guest.
        if ($profileUserID == $sessionUserID || $sessionUserID < 1) {
            return;
        }

        // Ensure that button is only shown if user would get a notification.
        $defaultPopup = Gdn::config('Preferences.Popup.Kick', true);
        $defaultEmail = Gdn::config('Preferences.Email.Kick', false);
        $userConfigPopup = $sender->User->Preferences['Popup.Kick'] ?? $defaultPopup;
        $userConfigEmail = $sender->User->Preferences['Email.Kick'] ?? $defaultEmail;
        if ($userConfigPopup == false && $userConfigEmail == false) {
            return;
        }

        $text = trim(sprite('SpKick').' '.t('Kick'));
        $url = '/plugin/kick?id='.$sender->User->UserID.'&tk='.Gdn::session()->transientKey();

        if (Gdn::config('kick.UseDropDownButton', false)) {
            // Enhance message button on profile with a second option
            $args['MemberOptions'][] = [
                'Text' => $text,
                'Url' => $url,
                'CssClass' => 'KickButton Hijack'
            ];
        } else {
            $args['ProfileOptions'][] = [
                'Text' => $text,
                'Url' => $url,
                'CssClass' => 'KickButton Hijack'
            ];
        }
    }


    public function profileController_afterAddSideMenu_handler($sender, $args) {
        $module = $sender->Assets['Content']['ProfileOptionsModule'] ?? null;
        // Only proceed if the module is available.
        if (!$module) {
            return;
        }
        // Don't change view if no separate button is needed
        if (Gdn::config('kick.UseDropDownButton', false)) {
            return;
        }
        $module->setView(Gdn::controller()->fetchViewLocation('profileoptions', '', '/plugins/kick'));
    }

    public function profileOptionsModule_render_before($sender) {
        decho(__LINE__); die;
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
