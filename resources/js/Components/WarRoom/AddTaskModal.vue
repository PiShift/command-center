<template>
  <div class="fixed inset-0 flex items-center justify-center" style="background:rgba(0,0,0,0.45);z-index:500" @click.self="$emit('close')">
    <div class="font-sans" style="background:white;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,0.18);padding:24px;width:460px;max-width:95vw">
      <h3 class="font-semibold" style="font-size:16px;color:#141413;margin-bottom:20px">Add Task</h3>

      <div style="margin-bottom:14px">
        <label class="block font-bold uppercase" style="font-size:11px;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Task</label>
        <textarea
          v-model="form.title"
          @keydown.enter.exact.prevent="save"
          class="w-full font-sans outline-none resize-none transition-colors"
          style="font-size:14px;color:#141413;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;padding:9px 12px"
          rows="2"
          placeholder="Describe the task…"
          autofocus
          @focus="e => { e.target.style.borderColor='#D97757'; e.target.style.background='white' }"
          @blur="e => { e.target.style.borderColor='#e5e4df'; e.target.style.background='#F5F4EF' }"
        ></textarea>
      </div>

      <div class="grid" style="grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
        <div>
          <label class="block font-bold uppercase" style="font-size:11px;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Project</label>
          <select v-model="form.project_id" class="w-full font-sans outline-none cursor-pointer modal-select">  
            <option :value="null">— None —</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
        </div>
        <!-- Assignee picker: users with reassign permission only -->
        <div v-if="can.reassign">
          <label class="block font-bold uppercase" style="font-size:11px;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Assignee</label>
          <select v-model="form.assigned_to" class="w-full font-sans outline-none cursor-pointer modal-select">
            <option :value="null">— None —</option>
            <option v-for="m in team" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
        </div>
        <div>
          <label class="block font-bold uppercase" style="font-size:11px;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Priority</label>
          <select v-model="form.priority" class="w-full font-sans outline-none cursor-pointer modal-select">
            <option value="medium">🟡 Medium</option>
            <option value="high">🔴 High</option>
            <option value="low">🟢 Low</option>
          </select>
        </div>
        <div>
          <label class="block font-bold uppercase" style="font-size:11px;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Status</label>
          <select v-model="form.status" class="w-full font-sans outline-none cursor-pointer modal-select">
            <option v-for="s in statuses" :key="s.slug" :value="s.slug">{{ s.name }}</option>
          </select>
        </div>
      </div>

      <div style="margin-bottom:14px">
        <label class="block font-bold uppercase" style="font-size:11px;letter-spacing:0.05em;color:#8c8c8a;margin-bottom:5px">Description (optional)</label>
        <textarea
          v-model="form.description"
          class="w-full font-sans outline-none resize-none transition-colors"
          style="font-size:14px;color:#141413;background:#F5F4EF;border:1px solid #e5e4df;border-radius:8px;padding:9px 12px"
          rows="2"
          placeholder="Any extra context…"
          @focus="e => { e.target.style.borderColor='#D97757'; e.target.style.background='white' }"
          @blur="e => { e.target.style.borderColor='#e5e4df'; e.target.style.background='#F5F4EF' }"
        ></textarea>
      </div>

      <div class="flex justify-end" style="gap:10px;margin-top:20px">
        <button
          @click="$emit('close')"
          class="font-medium cursor-pointer transition-colors"
          style="background:#F5F4EF;border:1px solid #e5e4df;color:#141413;font-size:13px;padding:8px 16px;border-radius:8px"
        >Cancel</button>
        <button
          @click="save"
          class="text-white font-medium cursor-pointer"
          style="background:#D97757;font-size:13px;padding:8px 16px;border-radius:8px;border:none"
          @mouseover="$event.target.style.background='#c4684a'"
          @mouseleave="$event.target.style.background='#D97757'"
        >Add Task</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  projects:      Array,
  team:          Array,
  statuses:      Array,
  defaultStatus: { type: String, default: null },
  can:           { type: Object, default: () => ({}) },
})
const emit = defineEmits(['close', 'saved'])

const form = reactive({
  title:       '',
  description: '',
  status:      props.defaultStatus ?? props.statuses?.[0]?.slug ?? 'backlog',
  project_id:  null,
  assigned_to: null,
  priority:    'medium',
})

function save() {
  if (!form.title.trim()) return
  router.post(route('tasks.store'), form, {
    preserveScroll: true,
    onSuccess: () => emit('saved'),
  })
}
</script>

<style scoped>
.modal-select {
  font-family: inherit;
  font-size: 14px;
  color: #141413;
  background: #F5F4EF;
  border: 1px solid #e5e4df;
  border-radius: 8px;
  padding: 9px 12px;
  outline: none;
  transition: border-color 0.15s, background 0.15s;
}
.modal-select:focus { border-color: #D97757; background: white; }
</style>
