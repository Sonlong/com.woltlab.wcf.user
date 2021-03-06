<?php
namespace wcf\page;
use wcf\data\object\type\ObjectTypeCache;
use wcf\data\user\follow\UserFollowerList;
use wcf\data\user\follow\UserFollowingList;
use wcf\data\user\profile\visitor\UserProfileVisitor;
use wcf\data\user\profile\visitor\UserProfileVisitorEditor;
use wcf\data\user\profile\visitor\UserProfileVisitorList;
use wcf\data\user\UserEditor;
use wcf\data\user\UserProfile;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\menu\user\profile\UserProfileMenu;
use wcf\system\request\LinkHandler;
use wcf\system\MetaTagHandler;
use wcf\system\WCF;

/**
 * Shows the user profile page.
 * 
 * @author	Marcel Werk
 * @copyright	2001-2013 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	com.woltlab.wcf.user
 * @subpackage	page
 * @category	Community Framework
 */
class UserPage extends AbstractPage {
	/**
	 * @see	wcf\page\AbstractPage::$activeMenuItem
	 */
	public $activeMenuItem = 'wcf.user.members';
	
	/**
	 * @see	wcf\page\AbstractPage::$enableTracking
	 */
	public $enableTracking = true;
	
	/**
	 * @see	wcf\page\AbstractPage::$neededPermissions
	 */
	public $neededPermissions = array('user.profile.canViewUserProfile');
	
	/**
	 * edit profile on page load
	 * @var	boolean
	 */
	public $editOnInit = false;
	
	/**
	 * overview editable content object type
	 * @var	wcf\data\object\type\ObjectType
	 */
	public $objectType = null;
	
	/**
	 * profile content for active menu item
	 * @var	string
	 */
	public $profileContent = '';
	
	/**
	 * user id
	 * @var	integer
	 */
	public $userID = 0;
	
	/**
	 * user object
	 * @var	wcf\data\user\UserProfile
	 */
	public $user = null;
	
	/**
	 * follower list
	 * @var	wcf\data\user\follow\UserFollowerList
	 */
	public $followerList = null;
	
	/**
	 * following list
	 * @var	wcf\data\user\follow\UserFollowingList
	 */
	public $followingList = null;
	
	/**
	 * visitor list
	 * @var	wcf\data\user\profile\visitor\UserProfileVisitorList
	 */
	public $visitorList = null;
		
	/**
	 * @see	wcf\page\IPage::readParameters()
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['id'])) $this->userID = intval($_REQUEST['id']);
		$this->user = UserProfile::getUserProfile($this->userID);
		if ($this->user === null) {
			throw new IllegalLinkException();
		}
		
		// check is Accessible
		if ($this->user->isProtected()) {
			throw new PermissionDeniedException();
		}
		
		if (isset($_REQUEST['editOnInit'])) $this->editOnInit = true;
	}
	
	/**
	 * @see	wcf\page\IPage::readData()
	 */
	public function readData() {
		parent::readData();
		
		// add breadcrumbs
		WCF::getBreadcrumbs()->add(new Breadcrumb(WCF::getLanguage()->get('wcf.user.members'), LinkHandler::getInstance()->getLink('MembersList')));
		
		// get profile content
		if ($this->editOnInit) {
			// force 'about' tab as primary if editing profile
			UserProfileMenu::getInstance()->setActiveMenuItem('about');
		}
		
		$activeMenuItem = UserProfileMenu::getInstance()->getActiveMenuItem();
		$contentManager = $activeMenuItem->getContentManager();
		$this->profileContent = $contentManager->getContent($this->user->userID);
		$this->objectType = ObjectTypeCache::getInstance()->getObjectTypeByName('com.woltlab.wcf.user.profileEditableContent', 'com.woltlab.wcf.user.profileAbout');
		
		// get followers
		$this->followerList = new UserFollowerList();
		$this->followerList->getConditionBuilder()->add('user_follow.followUserID = ?', array($this->userID));
		$this->followerList->sqlLimit = 10;
		$this->followerList->readObjects();
		
		// get following
		$this->followingList = new UserFollowingList();
		$this->followingList->getConditionBuilder()->add('user_follow.userID = ?', array($this->userID));
		$this->followingList->sqlLimit = 10;
		$this->followingList->readObjects();
		
		// get visitors
		$this->visitorList = new UserProfileVisitorList();
		$this->visitorList->getConditionBuilder()->add('user_profile_visitor.ownerID = ?', array($this->userID));
		$this->visitorList->sqlLimit = 10;
		$this->visitorList->readObjects();
		
		MetaTagHandler::getInstance()->addTag('og:url', 'og:url', LinkHandler::getInstance()->getLink('User', array('object' => $this->user->getDecoratedObject())), true);
		MetaTagHandler::getInstance()->addTag('og:type', 'og:type', 'profile', true);
		MetaTagHandler::getInstance()->addTag('og:profile:username', 'og:profile:username', $this->user->username, true);
		MetaTagHandler::getInstance()->addTag('og:title', 'og:title', WCF::getLanguage()->getDynamicVariable('wcf.user.profile', array('user' => $this->user)) . ' - ' . WCF::getLanguage()->get(PAGE_TITLE), true);
		MetaTagHandler::getInstance()->addTag('og:image', 'og:image', $this->user->getAvatar()->getURL(), true);
	}
	
	/**
	 * @see	wcf\page\IPage::assignVariables()
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign(array(
			'editOnInit' => $this->editOnInit,
			'overviewObjectType' => $this->objectType,
			'profileContent' => $this->profileContent,
			'userID' => $this->userID,
			'user' => $this->user,
			'followers' => $this->followerList->getObjects(),
			'followerCount' => $this->followerList->countObjects(),
			'following' => $this->followingList->getObjects(),
			'followingCount' => $this->followingList->countObjects(),
			'visitors' => $this->visitorList->getObjects(),
			'visitorCount' => $this->visitorList->countObjects()
		));
	}
	
	/**
	 * @see	wcf\page\IPage::show()
	 */
	public function show() {
		// update profile hits
		if ($this->user->userID != WCF::getUser()->userID && !WCF::getSession()->spiderID) {
			$editor = new UserEditor($this->user->getDecoratedObject());
			$editor->updateCounters(array('profileHits' => 1));
			
			// save visitor
			if (WCF::getUser()->userID && !WCF::getUser()->invisible) {
				if (($visitor = UserProfileVisitor::getObject($this->user->userID, WCF::getUser()->userID)) !== null) {
					$editor = new UserProfileVisitorEditor($visitor);
					$editor->update(array(
						'time' => TIME_NOW
					));
				}
				else {
					UserProfileVisitorEditor::create(array(
						'ownerID' => $this->user->userID,
						'userID' => WCF::getUser()->userID,
						'time' => TIME_NOW
					));
				}
			}
		}
		
		parent::show();
	}
	
	/**
	 * @see	wcf\page\ITrackablePage::getObjectType()
	 */
	public function getObjectType() {
		return 'com.woltlab.wcf.user';
	}
	
	/**
	 * @see	wcf\page\ITrackablePage::getObjectID()
	 */
	public function getObjectID() {
		return $this->userID;
	}
}
