<template>
  <div class="overflow-hidden" style="background:white;border:1px solid #eeeee9;border-radius:12px;box-shadow:0 1px 3px rgba(20,20,19,0.04)">
    <div class="flex items-center" style="gap:12px;padding:14px 16px;border-bottom:1px solid #eeeee9">
      <div
        class="flex items-center justify-center text-white font-semibold flex-shrink-0"
        style="width:36px;height:36px;border-radius:50%;font-size:14px"
        :style="{ background: member.color || '#D97757' }"
      >{{ member.initials || member.name.charAt(0).toUpperCase() }}</div>
      <div class="flex-1 min-w-0">
        <p class="font-semibold" style="font-size:15px;color:#141413">{{ member.name }}</p>
        <p v-if="member.role" style="font-size:12px;color:#8c8c8a;margin-top:1px">{{ member.role }}</p>
      </div>
      <span style="font-size:13px;color:#8c8c8a">{{ tasks.length }} active</span>
    </div>

    <div style="padding:12px">
      <div v-if="tasks.length === 0" class="text-center" style="padding:20px 0;font-size:13px;color:#8c8c8a">No active tasks</div>
      <div
        v-for="task in tasks" :key="task.id"
        class="flex items-center"
        style="gap:8px;padding:9px 8px;border-bottom:1px solid #eeeee9"
        :style="task === tasks[tasks.length - 1] ? 'border-bottom:none' : ''"
      >
        <div class="rounded-full flex-shrink-0" style="width:7px;height:7px" :style="{ background: statusColor(task.status) }"></div>
        <span class="flex-1 truncate" style="font-size:13px;color:#5c5c5a">{{ task.title }}</span>
        <span v-if="task.project" style="font-size:11px;color:#8c8c8a;background:#F5F4EF;padding:1px 6px;border-radius:4px">{{ task.project.name }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({ member: Object, tasks: Array })

function statusColor(s) {
  return {
    'backlog':     '#e5e4df',
    'in-progress': '#D97757',
    'in-review':  '#4a90d9',
    'done':       '#3d9970',
  }[s] || '#e5e4df'
}
</script>
