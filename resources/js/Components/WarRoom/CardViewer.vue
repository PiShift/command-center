<template>
  <div
    class="fixed inset-0 flex items-start justify-center overflow-y-auto"
    style="background:rgba(0,0,0,0.5);z-index:600;padding:40px 16px"
    @click.self="$emit('close')"
  >
    <div class="flex flex-col relative w-full my-auto" style="background:#F5F4EF;border-radius:16px;box-shadow:0 24px 80px rgba(0,0,0,0.22);max-width:780px">

      <!-- Close -->
      <button
        @click="$emit('close')"
        class="absolute flex items-center justify-center cursor-pointer transition-colors"
        style="top:14px;right:14px;width:30px;height:30px;border-radius:50%;background:rgba(0,0,0,0.07);color:#5c5c5a;font-size:16px;border:none;z-index:10"
        @mouseover="$event.target.style.background='rgba(0,0,0,0.14)'"
        @mouseleave="$event.target.style.background='rgba(0,0,0,0.07)'"
      >✕</button>

      <div class="flex flex-1">

        <!-- Left: main content -->
        <div class="flex-1 flex flex-col min-w-0" style="padding:28px;gap:24px">

          <!-- Editable title -->
          <textarea
            v-model="local.title"
            @blur="save('title')"
            class="w-full font-sans font-semibold leading-snug bg-transparent border border-transparent rounded-[6px] outline-none resize-none transition-all"
            style="font-size:20px;color:#141413;padding:6px 8px;margin:-6px -8px"
            rows="2"
            @mouseover="$event.target.style.background='rgba(0,0,0,0.03)'"
            @mouseleave="e => { if (document.activeElement !== e.target) e.target.style.background='transparent' }"
            @focus="e => { e.target.style.background='white'; e.target.style.borderColor='#e5e4df'; e.target.style.boxShadow='0 1px 3px rgba(0,0,0,0.04)' }"
            @blur.native="e => { e.target.style.background='transparent'; e.target.style.borderColor='transparent'; e.target.style.boxShadow='none' }"
          ></textarea>

          <!-- Description -->
          <div>
            <p class="font-bold uppercase" style="font-size:11px;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:8px">📝 Description</p>
            <textarea
              v-model="local.description"
              @blur="save('description')"
              class="w-full font-sans leading-relaxed outline-none resize-none transition-all"
              style="font-size:13.5px;color:#141413;background:rgba(0,0,0,0.03);border:1px solid transparent;border-radius:8px;padding:10px 12px;min-height:80px"
              rows="4"
              placeholder="Add a description or extra context…"
              @focus="e => { e.target.style.background='white'; e.target.style.borderColor='#D97757'; e.target.style.boxShadow='0 1px 3px rgba(0,0,0,0.04)' }"
              @blur.native="e => { e.target.style.background='rgba(0,0,0,0.03)'; e.target.style.borderColor='transparent'; e.target.style.boxShadow='none' }"
            ></textarea>
          </div>

          <!-- Comments placeholder -->
          <div>
            <p class="font-bold uppercase" style="font-size:11px;letter-spacing:0.06em;color:#8c8c8a;margin-bottom:12px">💬 Comments</p>
            <p style="font-size:13px;color:#8c8c8a;font-style:italic">Comments coming soon.</p>
          </div>

        </div>

        <!-- Right: sidebar -->
        <div class="flex flex-col flex-shrink-0" style="width:200px;padding:28px 20px 28px 4px;gap:4px">

          <div style="margin-bottom:16px">
            <p class="font-bold uppercase" style="font-size:10px;letter-spacing:0.08em;color:#8c8c8a;margin-bottom:6px">Status</p>
            <select v-model="local.status" @change="save('status')" class="sidebar-select">
              <option v-for="s in statuses" :key="s.slug" :value="s.slug">{{ s.name }}</option>
            </select>
          </div>

          <div style="margin-bottom:16px">
            <p class="font-bold uppercase" style="font-size:10px;letter-spacing:0.08em;color:#8c8c8a;margin-bottom:6px">Project</p>
            <select v-model="local.project_id" @change="save('project_id')" class="sidebar-select">
              <option :value="null">— None —</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>

          <div style="margin-bottom:16px">
            <p class="font-bold uppercase" style="font-size:10px;letter-spacing:0.08em;color:#8c8c8a;margin-bottom:6px">Assignee</p>
            <!-- Users with reassign permission can change assignee; others see read-only -->
            <select v-if="can.reassign" v-model="local.assigned_to" @change="save('assigned_to')" class="sidebar-select">
              <option :value="null">— Unassigned —</option>
              <option v-for="m in team" :key="m.id" :value="m.id">{{ m.name }}</option>
            </select>
            <p v-else style="font-size:13px;color:#141413">
              {{ task.assignee?.name || '— Unassigned —' }}
            </p>
          </div>

          <div style="margin-bottom:16px">
            <p class="font-bold uppercase" style="font-size:10px;letter-spacing:0.08em;color:#8c8c8a;margin-bottom:6px">Priority</p>
            <select v-model="local.priority" @change="save('priority')" class="sidebar-select">
              <option value="high">🔴 High</option>
              <option value="medium">🟡 Medium</option>
              <option value="low">🟢 Low</option>
            </select>
          </div>

          <div style="margin-bottom:16px">
            <p class="font-bold uppercase" style="font-size:10px;letter-spacing:0.08em;color:#8c8c8a;margin-bottom:6px">Created</p>
            <p style="font-size:12px;color:#8c8c8a">{{ formattedDate }}</p>
          </div>

          <hr style="border-color:#e5e4df;margin:4px 0">

          <!-- Delete: users with delete permission -->
          <button
            v-if="can.delete"
            @click="$emit('delete', task.id)"
            class="w-full text-center cursor-pointer transition-colors"
            style="background:transparent;border:1px solid #ffd0d0;color:#b94040;font-size:12px;font-weight:500;padding:8px;border-radius:8px;margin-top:auto"
            @mouseover="$event.target.style.background='#fff0f0'"
            @mouseleave="$event.target.style.background='transparent'"
          >🗑 Delete task</button>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  task:     Object,
  projects: Array,
  team:     Array,
  statuses: Array,
  can:      { type: Object, default: () => ({}) },
})
const emit = defineEmits(['close', 'update', 'delete'])

const local = ref({
  title:       props.task.title,
  description: props.task.description ?? '',
  status:      props.task.status,
  priority:    props.task.priority ?? 'medium',
  project_id:  props.task.project?.id ?? null,
  assigned_to: props.task.assigned_to ?? null,
})

watch(() => props.task, (t) => {
  local.value = {
    title:       t.title,
    description: t.description ?? '',
    status:      t.status,
    priority:    t.priority ?? 'medium',
    project_id:  t.project?.id ?? null,
    assigned_to: t.assigned_to ?? null,
  }
}, { deep: true })

const formattedDate = computed(() => {
  if (!props.task.created_at) return '—'
  return new Date(props.task.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
})

function save(field) {
  emit('update', { id: props.task.id, [field]: local.value[field] })
}
</script>

<style scoped>
.sidebar-select {
  width: 100%;
  font-family: inherit;
  font-size: 13px;
  padding: 7px 10px;
  border: 1px solid #e5e4df;
  border-radius: 8px;
  background: white;
  color: #141413;
  outline: none;
  cursor: pointer;
  transition: border-color 0.15s;
}
.sidebar-select:hover { border-color: #8c8c8a; }
.sidebar-select:focus { border-color: #D97757; }
</style>
