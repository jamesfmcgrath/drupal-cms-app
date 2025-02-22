<script>
  import { PACKAGE_MANAGER, MAX_SELECTIONS } from '../constants';
  import { openPopup, getCommandsPopupMessage } from '../popup';
  import ProjectButtonBase from './ProjectButtonBase.svelte';
  import ProjectStatusIndicator from './ProjectStatusIndicator.svelte';
  import ProjectIcon from './ProjectIcon.svelte';
  import LoadingEllipsis from './LoadingEllipsis.svelte';
  import {
    processInstallList,
    addToInstallList,
    installList,
    removeFromInstallList,
    updated,
  } from '../InstallListProcessor';

  // eslint-disable-next-line import/no-mutable-exports,import/prefer-default-export
  export let project;

  const { Drupal } = window;
  const processMultipleProjects = MAX_SELECTIONS === null || MAX_SELECTIONS > 1;

  $: isInInstallList = $installList.some((item) => item.id === project.id);

  // If MAX_SELECTIONS is null (no limit), then the install list is never full.
  const InstallListFull = $installList.length === MAX_SELECTIONS;

  let loading = false;

  function handleAddToInstallListClick(singleProject) {
    addToInstallList(singleProject);
    $updated = new Date().getTime();
  }

  function handleRemoveFromInstallList(projectId) {
    removeFromInstallList(projectId);
    $updated = new Date().getTime();
  }

  const onClick = async () => {
    if (processMultipleProjects) {
      if (isInInstallList) {
        handleRemoveFromInstallList(project.id);
      } else {
        handleAddToInstallListClick(project);
      }
    } else {
      handleAddToInstallListClick(project);
      loading = true;
      await processInstallList();
      loading = false;
      $updated = new Date().getTime();
    }
  };
</script>

<div class="pb-actions">
  {#if !project.is_compatible}
    <ProjectStatusIndicator {project} statusText={Drupal.t('Not compatible')} />
  {:else if project.status === 'active'}
    <ProjectStatusIndicator {project} statusText={Drupal.t('Installed')}>
      <ProjectIcon type="installed" />
    </ProjectStatusIndicator>
  {:else}
    <span>
      {#if PACKAGE_MANAGER}
        {#if isInInstallList && !processMultipleProjects}
          <ProjectButtonBase>
            <LoadingEllipsis />
          </ProjectButtonBase>
        {:else if InstallListFull && !isInInstallList && processMultipleProjects}
          <ProjectButtonBase disabled>
            {@html Drupal.t(
              'Select <span class="visually-hidden">@title</span>',
              {
                '@title': project.title,
              },
            )}
          </ProjectButtonBase>
        {:else}
          <ProjectButtonBase click={onClick}>
            {#if isInInstallList}
              {@html Drupal.t(
                'Deselect <span class="visually-hidden">@title</span>',
                {
                  '@title': project.title,
                },
              )}
            {:else if processMultipleProjects}
              {@html Drupal.t(
                'Select <span class="visually-hidden">@title</span>',
                {
                  '@title': project.title,
                },
              )}
            {:else}
              {@html Drupal.t(
                'Install <span class="visually-hidden">@title</span>',
                {
                  '@title': project.title,
                },
              )}
            {/if}
          </ProjectButtonBase>
        {/if}
      {:else if project.commands}
        {#if project.commands.match(/^https?:\/\//)}
          <a href={project.commands} target="_blank" rel="noreferrer"
            ><ProjectButtonBase>{Drupal.t('Install')}</ProjectButtonBase></a
          >
        {:else}
          <ProjectButtonBase
            aria-haspopup="dialog"
            click={() => openPopup(getCommandsPopupMessage(project), project)}
          >
            {@html Drupal.t(
              'View Commands <span class="visually-hidden">for @title</span>',
              {
                '@title': project.title,
              },
            )}
          </ProjectButtonBase>
        {/if}
      {/if}
    </span>
  {/if}
</div>
