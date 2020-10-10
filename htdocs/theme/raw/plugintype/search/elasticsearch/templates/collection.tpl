{if $record->deleted}
    <h2 class="list-group-item-heading text-inline">
        <span class="icon icon-folder-open left" role="presentation" aria-hidden="true"></span>
        {$record->name}
    </h2>
    <span class="artefacttype text-midtone">({str tag=deleted section=search.elasticsearch})</span>
{else}
    <h2 class="list-group-item-heading text-inline">
        <span class="icon float-left icon-folder-open left" role="presentation" aria-hidden="true"></span>
        {if $record->viewid}
        <a href="{$WWWROOT}view/view.php?id={$record->viewid}">
            {$record->name}
        </a>
        {else}
            {$record->name}
        {/if}
    </h2>
    <span class="artefacttype text-midtone">({str tag=collection section=search.elasticsearch})</span>
    <div class="row">
        <div class="col-md-7">
            {if $record->createdbyname}
                <div class="createdby text-small">{str tag=createdby section=search.elasticsearch arg1='<a href="`$record->createdby|profile_url`">`$record->createdbyname`</a>'}</div>
            {/if}
            <div class="detail text-small">
                {if $record->highlight}
                    {$record->highlight|safe}
                {else}
                    {$record->description|str_shorten_html:140:true|safe}
                {/if}
            </div>
            <!-- TAGS -->
            {if is_array($record->tags) && count($record->tags) > 0}
                <div class="tags text-small">
                    <strong>{str tag=tags section=search.elasticsearch}:</strong>
                    {foreach from=$record->tags item=tag name=tags}
                        <a href="{$WWWROOT}search/elasticsearch/index.php?query={$tag}&tagsonly=true">{$tag}</a>{if !$.foreach.tags.last}, {/if}
                    {/foreach}
                </div>
            {/if}
            <!-- end TAGS -->
        </div>
        <div class="col-md-5">
            <!-- PAGES -->
            {if is_array($record->views) && count($record->views) > 0}
                <div class="usedon text-small">
                {if count($record->views) > 1}
                    <strong>{str tag=views}:</strong>
                {else}
                    <strong>{str tag=view}:</strong>
                {/if}
                <ul class="list-group list-unstyled text-small">
                {foreach from=$record->views key=id item=view name=views}
                    <li><a href="{$WWWROOT}view/view.php?id={$id}">{$view|str_shorten_html:50:true|safe}</a></li>
                {/foreach}
                </ul>
                </div>
            {else}
                {str tag=none section=search.elasticsearch}
            {/if}
        </div>
    </div>
{/if}
