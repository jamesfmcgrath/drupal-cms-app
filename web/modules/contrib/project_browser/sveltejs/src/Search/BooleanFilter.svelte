<script>
  import { getContext } from 'svelte';

  export let definition;

  const { name: title, on_label: onLabel, off_label: offLabel } = definition;
  const filters = getContext('filters');

  export let changeHandler;
  export let name;
</script>

<div class="filter-group__filter-options form-item">
  <label for={name} class="form-item__label">{title}</label>
  <select
    {name}
    class="search__filter-select form-select form-element form-element--type-select"
    bind:value={$filters[name]}
    on:change={changeHandler}
  >
    <option value={true}>{onLabel}</option>
    <option value={false}>{offLabel}</option>
  </select>
</div>
