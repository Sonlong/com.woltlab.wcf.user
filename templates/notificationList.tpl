{include file='documentHeader'}

<head>
	<title>{lang}wcf.user.notification.notifications{/lang} - {lang}wcf.user.usercp{/lang} - {PAGE_TITLE|language}</title>
	{include file='headInclude'}
	
	<script type="text/javascript">
		//<![CDATA[
		$(function() {
			new WCF.Notification.List();
		});
		//]]>
	</script>
</head>

<body id="tpl{$templateName|ucfirst}">

{include file='userMenuSidebar'}

{include file='header' sidebarOrientation='left'}

<header class="boxHeadline">
	<hgroup>
		<h1>{lang}wcf.user.notification.notifications{/lang} <span class="badge jsNotificationsBadge">{#$notifications[count]}</span></h1>
	</hgroup>
</header>

{include file='userNotice'}

<div class="contentNavigation">
	{pages print=true assign=pagesLinks controller='NotificationList' link="pageNo=%d"}
</div>

{hascontent}
	<div class="container marginTop">
		<ul class="containerList">
			{content}
				{foreach from=$notifications[notifications] item=$notification}
					<li class="jsNotificationItem" data-notification-id="{@$notification[notificationID]}">
						<div class="box48">
							<a href="{link controller='User' object=$notification[author]}{/link}" title="{$notification[author]->username}" class="framed">{@$notification[author]->getAvatar()->getImageTag(48)}</a>
							
							<div class="details">
								<hgroup class="containerHeadline">
									<h1><a href="{link controller='User' object=$notification[author]}{/link}" class="userLink" data-user-id="{@$notification[author]->userID}">{$notification[author]->username}</a></h1> 
									<h2><small>{@$notification[time]|time}</small></h2>
								</hgroup>
								
								<p>{@$notification[message]}</p>
								
								<ul class="buttonList jsNotificationAction jsOnly" data-notification-id="{@$notification[notificationID]}">
									{foreach from=$notification[buttons] item=button}
										<li class="button small" data-action-name="{$button[actionName]}" data-class-name="{$button[className]}" data-object-id="{@$button[objectID]}">{$button[label]}</li>
									{/foreach}
								</ul>
							</div>
						</div>
					</li>
				{/foreach}
			{/content}
		</ul>
	</div>
	
	<div class="contentNavigation">
		{@$pagesLinks}
	</div>
{hascontentelse}
	<p class="info">{lang}wcf.user.notification.noNotifications{/lang}</p>
{/hascontent}

{include file='footer'}

</body>
</html>
