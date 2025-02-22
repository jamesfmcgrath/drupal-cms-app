import App from './App.svelte';

const element = document.querySelector('[data-project-browser-instance-id]');

const app = new App({
  // The #project-browser markup is returned by the project_browser.browse Drupal route.
  target: element,
  props: {
    id: element.getAttribute('data-project-browser-instance-id'),
  },
});

export default app;
