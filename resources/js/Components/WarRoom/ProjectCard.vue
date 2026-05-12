<template>
  <div
    class="cursor-pointer transition-all duration-150"
    style="background:white;border:1px solid #eeeee9;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(20,20,19,0.04)"
    @click="$emit('click')"
    @mouseover="e => { e.currentTarget.style.boxShadow='0 5px 16px rgba(20,20,19,0.08)'; e.currentTarget.style.borderColor='#e5e4df' }"
    @mouseleave="e => { e.currentTarget.style.boxShadow='0 1px 3px rgba(20,20,19,0.04)'; e.currentTarget.style.borderColor='#eeeee9' }"
  >
    <div class="flex items-center" style="gap:10px;margin-bottom:12px">
      <div class="rounded-full flex-shrink-0" style="width:10px;height:10px" :style="{ background: project.color || '#4a90d9' }"></div>
      <h3 class="font-semibold" style="font-size:15px;color:#141413">{{ project.name }}</h3>
    </div>

    <p v-if="project.description" class="leading-relaxed line-clamp-2" style="font-size:13px;color:#8c8c8a;margin-bottom:12px">
      {{ project.description }}
    </p>

    <div v-if="stackItems.length" class="flex flex-wrap" style="gap:5px;margin-bottom:16px">
      <span v-for="s in stackItems" :key="s" class="font-medium" style="font-size:11px;background:#F5F4EF;color:#5c5c5a;padding:2px 8px;border-radius:4px">{{ s }}</span>
    </div>

    <div class="flex" style="gap:16px;padding-top:12px;border-top:1px solid #eeeee9">
      <div style="font-size:12px;color:#8c8c8a"><strong style="color:#141413;font-weight:600">{{ total }}</strong> tasks</div>
      <div style="font-size:12px;color:#8c8c8a"><strong style="color:#141413;font-weight:600">{{ inprog }}</strong> in progress</div>
      <div style="font-size:12px;color:#8c8c8a"><strong style="color:#141413;font-weight:600">{{ done }}</strong> done</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ project: Object, tasks: Array })
defineEmits(['click'])

const pt     = computed(() => props.tasks.filter(t => t.project?.id === props.project.id))
const total  = computed(() => pt.value.length)
const inprog = computed(() => pt.value.filter(t => t.status === 'in-progress').length)
const done   = computed(() => pt.value.filter(t => t.status === 'done').length)

const stackItems = computed(() => {
  const s = props.project.stack
  if (!s) return []
  if (Array.isArray(s)) return s
  try { return JSON.parse(s) } catch { return [s] }
})
</script>
