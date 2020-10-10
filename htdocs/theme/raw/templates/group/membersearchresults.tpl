{if $results.data}
{foreach from=$results.cdata item=row}
{foreach from=$row item=r}
<div class="list-group-item {if $membershiptype == 'invite' || $membershiptype == 'request'} list-group-item-warning{/if}">
     <div class="row">
        <div class="col-md-8">
            <div class="user-icon user-icon-40 float-left">
                <a href="{profile_url($r)}"><img src="{profile_icon_url user=$r maxwidth=40 maxheight=40}" alt="{str tag=profileimagetext arg1=$r|display_default_name}"></a>
            </div>

            <h3 class="list-group-item-heading">
                <a href="{profile_url($r)}">{$r.name}</a>
                {if $r.role}
                <span class="grouprole text-small text-midtone"> -
                    {$results.roles[$r.role]->display}
                    {if $caneditroles && $r.canchangerole}
                    <a href="{$WWWROOT}group/changerole.php?group={$group}&amp;user={$r.id}" class="inner-link text-link">
                        [{str tag=changerole section=group}]
                    </a>
                    {/if}
                </span>
                {/if}
            </h3>

            {if $r.role}
                {if $r.introduction}
                    <div class="detail text-small text-midtone">
                        <a class="inner-link text-link collapsed with-introduction" data-toggle="collapse" data-target="#userintro{$r.id}"
                           href="#userintro{$r.id}" role="button" aria-expanded="false"
                           aria-controls="userintro{$r.id}">
                           <span class="icon icon-chevron-down collapse-indicator text-inline" role="presentation" aria-hidden="true"></span>
                           {str tag=showintroduction section=group}
                        </a>
                    </div>
                {/if}
            {/if}

            {if $r.role}
            <div class="introduction detail text-small">

                <div class="collapse" id="userintro{$r.id}">{$r.introduction|safe}
                </div>
                <div class="jointime">
                    <strong>
                        {str tag="Joined" section="group"}:
                    </strong>
                    {$r.jointime}
                </div>
            </div>
            {elseif $membershiptype == 'request'}
            <div class="requestedmembership detail text-small">
                {str tag=hasrequestedmembership section=group}.
                {if $r.reason}
                <div>
                    <strong>{str tag=reason}:</strong>
                    {$r.reason|format_whitespace|safe}
                </div>
                {/if}
            </div>
            {elseif $membershiptype == 'invite'}
            <div class="invited detail text-small">
                {str tag=hasbeeninvitedtojoin section=group}
            </div>
            {/if}
        </div>
        <div class="col-md-4">
            <div class="btn-action-list">
                <div class="btn-top-right btn-group btn-group-top">
                    {if $r.role}
                        {$r.removeform|safe}
                    {elseif $membershiptype == 'request'}
                        {$r.addform|safe}
                        {$r.denyform|safe}
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>
{/foreach}
{/foreach}
{else}
<div class="card-body">
    <p class="no-results">{str tag="noresultsfound"}</p>
</div>
{/if}
