<div class="friends card-body">
{if $friends}
    <div id="userfriendstable">
       {$friends.tablerows|safe}
    </div>
    <div id="myfriends_page_container" class="d-none ">
        {$friends.pagination|safe}
    </div>
    <script>
    jQuery(function($) {literal}{{/literal}
        {$friends.pagination_js|safe}
        $('#myfriends_page_container').removeClass('d-none');
        $(window).on('pageupdated', { }, function() {
            $('.js-masonry.user-thumbnails').masonry({ itemSelector: '.user-icon' });
        });
    {literal}}{/literal});
    </script>
{else}
    {if $lookingatownpage}
        <div class="text-center">
            {str tag="trysearchingforfriends" section=group args=$searchingforfriends}
        </div>
    {elseif $loggedin}
        {if $relationship == 'none' && $friendscontrol == 'auto'}
            {$newfriendform|safe}
        {elseif $relationship == 'none' && $friendscontrol == 'auth'}
        <div class="text-center">
            <a href="{$WWWROOT}user/requestfriendship.php?id={$USERID}&amp;returnto=view">
                {str tag='requestfriendship' section='group'}
            </a>
        </div>
        {elseif $relationship == 'requestedfriendship'}
        <div class="text-center">
            {str tag=friendshiprequested section=group}
        </div>
        {/if}
        {* Case not covered here: friendscontrol disallows new users. The block will appear empty. *}
    {/if}
{/if}
</div>
