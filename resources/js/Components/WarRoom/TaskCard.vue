<template>
  <div
    class="cursor-pointer select-none transition-all duration-150"
    style="background:white;border:1px solid #eeeee9;border-radius:10px;padding:13px;box-shadow:0 1px 3px rgba(20,20,19,0.04)"
    draggable="true"
    @dragstart="$emit('dragstart')"
    @dragend="$emit('dragend')"
    @click="$emit('click')"
    @mouseover="e => { e.currentTarget.style.boxShadow='0 4px 14px rgba(20,20,19,0.08)'; e.currentTarget.style.borderColor='#e5e4df' }"
    @mouseleave="e => { e.currentTarget.style.boxShadow='0 1px 3px rgba(20,20,19,0.04)'; e.currentTarget.style.borderColor='#eeeee9' }"
  >
    <p class="font-medium leading-snug" style="font-size:13.5px;color:#141413">{{ task.title }}</p>

    <p v-if="task.description" class="leading-relaxed line-clamp-2" style="font-size:12px;color:#8c8c8a;margin-top:5px">
      {{ task.description }}
    </p>

    <div class="flex flex-wrap items-center" style="gap:5px;margin-top:9px">
      <span
        v-if="task.project"
        class="font-semibold border-l-2"
        style="font-size:11px;padding:2px 8px;border-radius:4px;background:#eef3fb;color:#3a6fba"
        :style="{ borderColor: task.project.color || '#4a90d9' }"
      >{{ task.project.name }}</span>

      <span
        v-if="task.assignee"
        class="font-semibold"
        style="font-size:11px;padding:2px 8px;border-radius:4px;background:#fdf3ee;color:#b55a2f"
      >{{ task.assignee.initials }}</span>

      <span v-if="task.priority === 'high'"   class="font-semibold" style="font-size:11px;padding:2px 8px;border-radius:4px;background:#fdf0f0;color:#b94040">↑ High</span>
      <span v-if="task.priority === 'medium'" class="font-semibold" style="font-size:11px;padding:2px 8px;border-radius:4px;background:#fdf8ec;color:#8a6300">→ Med</span>
      <span v-if="task.priority === 'low'"    class="font-semibold" style="font-size:11px;padding:2px 8px;border-radius:4px;background:#edf7f2;color:#2e7d55">↓ Low</span>
    </div>
  </div>
</template>

<script setup>
defineProps({ task: Object })
defineEmits(['click', 'dragstart', 'dragend'])
</script>
