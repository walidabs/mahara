{if $filelist}
<div class="filelist-container">
    <table id="{$prefix}_filelist" class="tablerenderer filelist table table-hover">
        <thead>
            <tr>
                <th class="icon-cell"></th>
                <th>{str tag=Name section=artefact.file}</th>
                <th class="d-none d-md-block">{str tag=Descriptionandtags section=artefact.file}</th>
                <th class="filesize">
                    {str tag=Size section=artefact.file}
                </th>
                {if !$selectable}
                <th class="filedate">
                    {str tag=Date section=artefact.file}
                </th>
                {/if}
                {if $editmeta || $selectable}
                <th class="right nowrap"></th>
                {/if}
                {if $editable && !$file->isparent}
                <th class="right nowrap"></th>
                {/if}
            </tr>
        </thead>

        <tbody>
        {foreach from=$filelist item=file}
            {if !$publishing || !$file->permissions || $file->can_republish}
                {assign var=publishable value=1}
            {else}
                {assign var=publishable value=0}
            {/if}

            <tr id="file:{$file->id}" class="file-item {if $file->isparent} parentfolder{/if}{if $highlight && $highlight == $file->id} active{/if}{if $file->artefacttype == 'folder'} folder{else}{if !$publishable } disabled {/if}{if $file->artefacttype == 'profileicon'} profileicon{/if}{/if}{if $edit == $file->id} d-none{/if}{if $selectable && ($file->artefacttype != 'folder' || $selectfolders) && $publishable && !$file->isparent} js-file-select {else} no-hover{/if}{if $file->locked} warning{/if}" {if $selectable && ($file->artefacttype != 'folder' || $selectfolders) && $publishable && !$file->isparent} data-id="{$file->id}" data-select="select-file" {/if} {if !$publishable && $file->artefacttype != 'folder'} title="{str tag=notpublishable section=artefact.file}"{/if}>

            {assign var=displaytitle value=$file->title|safe}
            <td class="icon-cell">

                {if $file->isparent}
                    {if $file->artefacttype == 'folder'}
                        <a href="{$querybase|safe}folder={$file->id}{if $owner}&owner={$owner}{if $ownerid}&ownerid={$ownerid}{/if}{/if}" id="changefolder-icon:{$file->id}" class="changefolder">
                            <span class="icon-level-up-alt icon icon-lg text-default" role="presentation" aria-hidden="true">
                            </span>
                            <span class="sr-only">
                                {str tag=folder section=artefact.file}:{$displaytitle}
                            </span>
                        </a>
                    {/if}
                {else}
                    {if $editable}
                    <div class="icon-drag" id="drag:{$file->id}" tabindex="0">
                        <span class="sr-only">{str tag=clickanddragtomovefile section=artefact.file arg1=$file->title}</span>
                    {/if}
                    {if $file->artefacttype == 'folder'}
                        {if $selectable}
                        <a href="{$querybase|safe}folder={$file->id}{if $owner}&owner={$owner}{if $ownerid}&ownerid={$ownerid}{/if}{/if}" id="changefolder:{$file->id}" class="changefolder" title="{str tag=folder section=artefact.file} {$displaytitle}">
                            <span class="icon-folder-open icon icon-lg" role="presentation" aria-hidden="true"></span>
                        </a>
                        {else}
                            <span class="icon-folder-open icon icon-lg " role="presentation" aria-hidden="true"></span>
                        {/if}
                    {else}
                        {if $file->icon}
                            <img role="presentation" aria-hidden="true" src="{$file->icon}" title="{str tag=clickanddragtomovefile section=artefact.file arg1=$file->title}" alt="{$file->title}">
                        {else}
                            <span class="icon icon-{$file->artefacttype} icon-lg" role="presentation" aria-hidden="true"></span>
                        {/if}
                    {/if}
                    {if $editable}
                    </div>
                    {/if}
                {/if}
            </td>
            <td class="filename">
                {if $file->artefacttype == 'folder'}
                    <a href="{$querybase|safe}folder={$file->id}{if $owner}&owner={$owner}{if $ownerid}&ownerid={$ownerid}{/if}{/if}" id="changefolder:{$file->id}" class="inner-link changefolder">
                        <span class="sr-only">{str tag=folder section=artefact.file}:</span>
                        <span class="display-title {if $file->isparent}accessible-hidden{/if}">{$displaytitle}</span>
                    </a>
                {elseif !$publishable}
                    <span class="display-title">{$displaytitle}</span>
                {else}
                    <a href="{$WWWROOT}artefact/file/download.php?file={$file->id}" title="{str tag=downloadfile section=artefact.file arg1=$displaytitle}" class="file-download-link inner-link {if $file->artefacttype == 'image' || $file->artefacttype == 'profileicon'}img-modal-preview{/if}">
                        <span class="display-title">{$displaytitle}</span>
                    </a>
                {/if}
            </td>
            <td class="filedescription d-none d-md-table-cell text-small">
                {$file->description|clean_html|safe}
                {if $showtags}
                    {if $file->tags}
                    <div class="tags filetags text-small">
                        {str tag=tags}:
                        <span>
                            {list_tags tags=$file->tags owner=$tagsowner showtags=$showtags}
                        </span>
                    </div>
                    {/if}
                {/if}
            </td>

            <td class="filesize text-small">{if $file->foldersize}{$file->foldersize}{elseif $file->size}{$file->size}{/if}</td>
            {if !$selectable}
            <td class="filedate text-small">{tif $file->mtime ?: ''}</td>
            {/if}
            {if $editmeta || $selectable}
            <td class="text-right control-buttons text-small">
                <div class="btn-group">
                {if $selectable && ($file->artefacttype != 'folder' || $selectfolders) && $publishable && !$file->isparent}
                    <button type="button" class="btn btn-sm btn-secondary" name="{$prefix}_select[{$file->id}]" id="{$prefix}_select_{$file->id}" title="{str tag=select}">
                        <span class="icon icon-check" role="presentation" aria-hidden="true"></span>
                        <span class="sr-only">{str tag=selectspecific section=artefact.file arg1=$displaytitle|escape:html|safe}</span>
                    </button>
                {/if}
                {if $editmeta}
                    {if $file->locked}
                        <span class="dull text-muted">{str tag=Submitted section=view}</span>
                    {elseif !$file->isparent}
                        {if !isset($file->can_edit) || $file->can_edit !== 0}
                        <button name="{$prefix}_edit[{$file->id}]" class="btn btn-secondary btn-sm editable" title="{str tag=edit}">
                            <span class="icon icon-pencil-alt" role="presentation" aria-hidden="true"></span>
                            {if $file->artefacttype == 'folder'}
                                <span class="sr-only">{str tag=editfolderspecific section=artefact.file arg1=$displaytitle|escape:html|safe}</span>
                            {else}
                                <span class="sr-only">{str tag=editfilespecific section=artefact.file arg1=$displaytitle|escape:html|safe}</span>
                            {/if}
                        </button>
                        {/if}
                    {/if}
                {/if}
                </div>
            </td>
            {/if}
            <!-- Ensure space for 3 buttons (in the case of a really long single line string in a user input field -->
            {if $editable && !$file->isparent}
            <td class="text-right control-buttons {if $file->artefacttype == 'archive'}includes-unzip{/if}">
                {if $file->locked}
                    <span class="dull text-muted">
                        {str tag=Submitted section=view}
                    </span>
                {elseif !isset($file->can_edit) || $file->can_edit != 0}
                    <div class="btn-group">
                        {if $file->artefacttype == 'archive'}
                        <a href="{$WWWROOT}artefact/file/extract.php?file={$file->id}" title="{str tag=Decompress section=artefact.file}" class="btn btn-secondary btn-sm">
                            <span class="icon icon-regular icon-file-archive" role="presentation" aria-hidden="true"></span>
                            <span class="sr-only">
                                {str(tag=decompressspecific section=artefact.file arg1=$displaytitle)|escape:html|safe}
                            </span>
                        </a>
                        {/if}

                        {if $file->artefacttype == 'folder'}
                            {assign var=edittext value=str(tag=editfolderspecific section=artefact.file arg1=$displaytitle)}
                            {assign var=deletetext value=str(tag=deletefolderspecific section=artefact.file arg1=$displaytitle)}
                        {else}
                            {assign var=edittext value=str(tag=editspecific arg1=$displaytitle)}
                            {assign var=deletetext value=str(tag=deletespecific arg1=$displaytitle)}
                        {/if}

                        <button name="{$prefix}_edit[{$file->id}]" class="btn btn-secondary btn-sm">
                            <span class="icon icon-pencil-alt" role="presentation" aria-hidden="true"></span>
                            <span class="sr-only">{$edittext|escape:html|safe}</span>
                        </button>

                        <button name="{$prefix}_delete[{$file->id}]" class="btn btn-secondary btn-sm">
                            <span class="icon icon-trash-alt text-danger" role="presentation" aria-hidden="true"></span>
                            <span class="sr-only">{$deletetext|escape:html|safe}</span>
                        </button>
                    </div>
                {/if}
            </td>
            {/if}
        </tr>
        {if $edit == $file->id}
            {include file="artefact:file:form/editfile.tpl" prefix=$prefix fileinfo=$file groupinfo=$groupinfo colspan=$colspan}
        {/if}

        {/foreach}
        </tbody>
    </table>
</div>
{if !$selectable && $downloadfolderaszip}
    <a id="downloadfolder" class="card-footer text-small" href="{$WWWROOT}artefact/file/downloadfolder.php?{$folderparams|safe}">
        <span class="icon icon-download" role="presentation" aria-hidden="true"></span>
        <span>{str tag=downloadfolderziplink section=artefact.file}</span>
    </a>
{/if}

{else}
<div class="card-body">
    <p class="no-results">
        {str tag=nofilesfound section=artefact.file}
    </p>
</div>
{/if}
