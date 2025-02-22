<script>
  import { createEventDispatcher, getContext } from 'svelte';

  const { Drupal } = window;
  const dispatch = createEventDispatcher();
  const filters = getContext('filters');

  // eslint-disable-next-line import/prefer-default-export
  export let choices;
  export let name;
  export let filterList;

  let filterVisible = false;
  let lastFocusedCheckbox = null;

  function showHideFilter() {
    filterVisible = !filterVisible;
    const dropdownItems = document.querySelector(
      '.pb-filter__multi-dropdown__items',
    );
    if (filterVisible) {
      dropdownItems.classList.add('pb-filter__multi-dropdown__items--visible');
    } else {
      dropdownItems.classList.remove(
        'pb-filter__multi-dropdown__items--visible',
      );
    }
    setTimeout(() => {
      // Ensure focus stays on the last focused checkbox
      if (lastFocusedCheckbox) {
        lastFocusedCheckbox.focus();
      } else {
        document.getElementsByClassName('pb-filter__checkbox')[0].focus();
      }
    }, 50);
  }

  function onBlur(event) {
    if (
      event.relatedTarget === null ||
      !document
        .getElementsByClassName('pb-filter__multi-dropdown')[0]
        .contains(event.relatedTarget)
    ) {
      filterVisible = false;
      const dropdownItems = document.querySelector(
        '.pb-filter__multi-dropdown__items',
      );
      dropdownItems.classList.remove(
        'pb-filter__multi-dropdown__items--visible',
      );
    }
  }

  function onKeyDown(event) {
    const checkboxes = document.querySelectorAll('.pb-filter__checkbox');
    if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
      event.preventDefault();
      return;
    }
    // Space to open category filter drop-down.
    if (
      event.key === ' ' &&
      event.target.classList.contains('pb-filter__multi-dropdown')
    ) {
      showHideFilter();
      event.preventDefault();
      return;
    }
    // Alt Up/Down opens/closes category filter drop-down.
    if (
      event.altKey &&
      (event.key === 'ArrowDown' || event.key === 'ArrowUp')
    ) {
      showHideFilter();
      event.preventDefault();
      return;
    }
    // Prevent tabbing out when the filter is expanded.
    if (event.key === 'Tab' && filterVisible) {
      event.preventDefault();
      return;
    }
    // Down arrow on checkbox moves to next checkbox or wraps around.
    if (
      event.target.classList.contains('pb-filter__checkbox') &&
      event.key === 'ArrowDown'
    ) {
      const nextElement =
        event.target.parentElement.parentElement.nextElementSibling;
      if (nextElement) {
        nextElement.firstElementChild.focus();
      } else {
        // Wrap to the first item
        checkboxes[0].focus();
      }
      event.preventDefault();
      return;
    }

    // Up arrow on checkbox moves to previous checkbox or wraps around.
    if (
      event.target.classList.contains('pb-filter__checkbox') &&
      event.key === 'ArrowUp'
    ) {
      const prevElement =
        event.target.parentElement.parentElement.previousElementSibling;
      if (prevElement) {
        prevElement.firstElementChild.focus();
      } else {
        // Wrap to the last item
        checkboxes[checkboxes.length - 1].focus();
      }
      event.preventDefault();
      return;
    }
    // Prevent dropdown collapse when moving focus with the arrow key.
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
    }
    // Tab moves off filter.
    if (event.key === 'Tab') {
      if (event.shiftKey) {
        // Shift+tab moves to search box.
        document.getElementById('pb-text').focus();
        event.preventDefault();
        return;
      }
      // Tab without shift moves to next filter.
      const indexOfMyself = filterList.indexOf(name);
      if (indexOfMyself !== -1 && indexOfMyself + 1 < filterList.length) {
        const nextElementKey = filterList[indexOfMyself + 1];
        document.getElementsByName(nextElementKey)[0].focus();
        event.preventDefault();
      }
      return;
    }

    // Escape closes filter drop-down.
    if (
      event.target.classList.contains('pb-filter__checkbox') &&
      event.key === 'Escape'
    ) {
      filterVisible = false;
      document.getElementsByClassName('pb-filter__multi-dropdown')[0].focus();
      const dropdownItems = document.querySelector(
        '.pb-filter__multi-dropdown__items',
      );
      dropdownItems.classList.remove(
        'pb-filter__multi-dropdown__items--visible',
      );
    }
  }

  async function onChange(event) {
    dispatch('FilterChange');
    filterVisible = true;
    const dropdownItems = document.querySelector(
      '.pb-filter__multi-dropdown__items',
    );
    dropdownItems.classList.add('pb-filter__multi-dropdown__items--visible');
    if (event.target.classList.contains('pb-filter__checkbox')) {
      lastFocusedCheckbox = event.target;
      setTimeout(() => {
        lastFocusedCheckbox.focus();
      }, 50);
    }
  }
</script>

<div class="filter-group__filter-options form-item">
  <label for="pb-text" class="form-item__label"
    >{Drupal.t('Filter by category')}</label
  >
  <div
    role="group"
    tabindex="0"
    class="pb-filter__multi-dropdown form-element form-element--type-select"
    on:click={() => {
      showHideFilter();
    }}
    on:blur={onBlur}
    on:keydown={onKeyDown}
  >
    <span class="pb-filter__multi-dropdown__label">
      {#if $filters[name].length > 0}
        {@html Drupal.formatPlural(
          $filters[name].length,
          '1 category selected',
          '@count categories selected',
        )}
      {:else}
        {Drupal.t('Select categories')}
      {/if}
    </span>
    <div
      class="pb-filter__multi-dropdown__items
      pb-filter__multi-dropdown__items--{filterVisible ? 'visible' : 'hidden'}"
    >
      {#each Object.entries(choices) as [id, name]}
        <div class="pb-filter__checkbox__container">
          <label for={id}>
            <input
              type="checkbox"
              {id}
              class="pb-filter__checkbox form-checkbox form-boolean form-boolean--type-checkbox"
              bind:group={$filters.categories}
              on:change={onChange}
              on:blur={onBlur}
              on:keydown={onKeyDown}
              value={id}
            />
            {name}
          </label>
        </div>
      {/each}
    </div>
  </div>
</div>
