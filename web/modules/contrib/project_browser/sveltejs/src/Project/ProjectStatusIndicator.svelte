<script>
  export let project;
  export let statusText;
  let buttonClasses = '';
  let buttonLabelClasses = '';

  const { Drupal } = window;
  $: {
    // @see css/pb.css
    if ('gin' in drupalSettings && drupalSettings.gin.darkmode === '1') {
      buttonClasses = 'project_status-indicator-gin';
      buttonLabelClasses = 'project_status-indicator__label-gin';
    } else {
      buttonLabelClasses = 'project_status-indicator__label';
    }
  }
</script>

<button class="project_status-indicator {buttonClasses}" aria-disabled="true">
  <slot />
  <span class="visually-hidden">
    {Drupal.t('@module is', {
      '@module': `${project.title}`,
    })}
  </span>
  <span class={buttonLabelClasses}>{statusText}</span>
</button>
