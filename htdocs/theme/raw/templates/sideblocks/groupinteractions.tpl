<div class="card">
    <h2 class="card-header">{str tag="groupinteractions" section="group"}</h2>

    {if $sbdata}
        <ul class="list-group">
        {foreach from=$sbdata.interactiontypes item=interactions key=plugin}
            <li class="list-group-item">
            {if $sbdata.membership}
                <a href="{$WWWROOT}interaction/{$plugin}/index.php?group={$sbdata.group}">{str tag=nameplural section='interaction.$plugin}</a>
            {else}
                {str tag=nameplural section='interaction.$plugin}
            {/if}
            {if $interactions}
                <ul>
                {foreach from=$interactions item=interaction}
                    <li>
                    {if $sbdata.membership}
                    <a href="{$WWWROOT}interaction/{$interaction->plugin}/view.php?id={$interaction->id}">{$interaction->title}</a>
                    {else}
                    {$interaction->title}
                    {/if}
                    </li>
                {/foreach}
                </ul>
            {/if}
            </li>
        {/foreach}
        </ul>
    {else}
        <div class="card-body">
            <p class="metadata">{str tag=nointeractions section=group}</p>
        </div>
    {/if}
</div>
