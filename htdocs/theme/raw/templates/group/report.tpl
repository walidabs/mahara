{include file="header.tpl"}
<h2>{str tag=viewscollectionssharedtogroup section=view}</h2>
{if $sharedviews.count == '0'}
<p class="no-results">
    {str tag=noviewssharedwithgroupyet section=group}
</p>
{else}
<table id="sharedviewsreport" class="fullwidth groupreport table table-striped">
    <thead>
        <tr>
            <th class="sv {if $sort == title && $direction == asc}asc{elseif $sort == title}sorted{/if}">
                <a href="{$baseurl}&sort=title{if $sort == title && $direction == asc}&direction=desc{/if}">{str tag=sharedtogroup section=view}</a>
            </th>
            <th class="sb {if $sort == owner && $direction == asc}asc{elseif $sort == owner}sorted{/if}">
                <a href="{$baseurl}&sort=owner{if $sort == owner && $direction == asc}&direction=desc{/if}">{str tag=sharedby section=view}</a>
            </th>
            <th class="mc {if $sort == membercommentcount && $direction == asc}asc{elseif $sort == membercommentcount}sorted{/if}">
                <a href="{$baseurl}&sort=membercommentcount{if $sort == membercommentcount && $direction == asc}&direction=desc{/if}">{str tag=membercommenters section=group}</a>
            </th>
            <th class="ec {if $sort == nonmembercommentcount && $direction == asc}asc{elseif $sort == nonmembercommentcount}sorted{/if}">
                <a href="{$baseurl}&sort=nonmembercommentcount{if $sort == nonmembercommentcount && $direction == asc}&direction=desc{/if}">{str tag=extcommenters section=group}</a>
            </th>
        </tr>
    </thead>
    <tbody>
        {$sharedviews.tablerows|safe}
    </tbody>
</table>
    {$sharedviews.pagination|safe}
    {if $sharedviews.pagination_js}
    <script>
    {$sharedviews.pagination_js|safe}
    </script>
    {/if}
{/if}

<h2>{str tag=groupviews section=view}</h2>
{if $groupviews.count == '0'}
<p class="no-results">
     {str tag=grouphasntcreatedanyviewsyet section=group}
</p>
{else}
<table id="groupviewsreport" class="fullwidth groupreport table table-striped">
    <thead>
        <tr>
            <th class="sv {if $sort == title && $direction == asc}asc{elseif $sort == title}sorted{/if}">
                <a href="{$baseurl}&sort=title{if $sort == title && $direction == asc}&direction=desc{/if}">
                    {str tag=ownedbygroup section=view}
                </a>
            </th>
            <th class="mc {if $sort == membercommentcount && $direction == asc}asc{elseif $sort == membercommentcount}sorted{/if}">
                <a href="{$baseurl}&sort=membercommentcount{if $sort == membercommentcount && $direction == asc}&direction=desc{/if}">
                    {str tag=membercommenters section=group}
                </a>
            </th>
            <th class="ec {if $sort == nonmembercommentcount && $direction == asc}asc{elseif $sort == nonmembercommentcount}sorted{/if}">
                <a href="{$baseurl}&sort=nonmembercommentcount{if $sort == nonmembercommentcount && $direction == asc}&direction=desc{/if}">{str tag=extcommenters section=group}
                </a>
            </th>
        </tr>
    </thead>
    <tbody>
        {$groupviews.tablerows|safe}
    </tbody>
</table>
{$groupviews.pagination|safe}
    {if $groupviews.pagination_js}
    <script>
    {$groupviews.pagination_js|safe}
    </script>
    {/if}
{/if}
{include file="footer.tpl"}
