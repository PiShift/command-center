<template>
  <div class="flex flex-col h-screen overflow-hidden font-sans" style="background:#faf9f5">

    <!-- Header -->
    <header class="flex items-center justify-between px-6 border-b flex-shrink-0 gap-4" style="padding-top:14px;padding-bottom:14px;border-color:#e5e4df;background:#faf9f5">
      <div class="flex items-center" style="gap:14px">
        <div class="w-8 h-8 rounded-[8px] flex items-center justify-center text-white font-bold flex-shrink-0" style="background:#D97757;font-size:15px">P</div>
        <span class="font-semibold tracking-[-0.02em]" style="font-size:1.1rem;color:#141413">Pi<span style="color:#D97757">Shift</span></span>
      </div>

      <!-- Tabs — Team tab only visible to admins -->
      <nav class="flex p-[4px] rounded-[8px]" style="gap:2px;background:#F5F4EF">
        <button
          v-for="tab in visibleTabs" :key="tab.id"
          @click="activeTab = tab.id"
          class="rounded-[6px] transition-all duration-150 cursor-pointer"
          style="padding:7px 16px;font-size:13px;font-weight:500"
          :style="activeTab === tab.id
            ? 'background:white;color:#141413;box-shadow:0 1px 3px rgba(20,20,19,0.04)'
            : 'background:transparent;color:#8c8c8a'"
        >{{ tab.label }}</button>
      </nav>

      <div class="flex items-center" style="gap:10px">
        <template v-if="activeTab === 'board'">
          <!-- Project filter: admins see all, users see their projects only -->
          <select v-model="filterProject" class="font-sans outline-none cursor-pointer" style="font-size:13px;color:#141413;background:white;border:1px solid #e5e4df;border-radius:7px;padding:6px 10px">
            <option value="">All Projects</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">{{ p.name }}</option>
          </select>
          <!-- Member filter: team-visible users only -->
          <select v-if="can.seeTeam" v-model="filterAssignee" class="font-sans outline-none cursor-pointer" style="font-size:13px;color:#141413;background:white;border:1px solid #e5e4df;border-radius:7px;padding:6px 10px">
            <option value="">All Members</option>
            <option v-for="m in team" :key="m.id" :value="m.id">{{ m.name }}</option>
          </select>
          <select v-model="filterPriority" class="font-sans outline-none cursor-pointer" style="font-size:13px;color:#141413;background:white;border:1px solid #e5e4df;border-radius:7px;padding:6px 10px">
            <option value="">All Priorities</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </template>

        <!-- Auth user avatar -->
        <div
          v-if="auth.user"
          class="flex items-center justify-center text-white font-semibold flex-shrink-0"
          style="width:32px;height:32px;border-radius:50%;font-size:12px"
          :style="{ background: auth.user.color || '#D97757' }"
          :title="auth.user.name"
        >{{ auth.user.initials || auth.user.name.charAt(0) }}</div>

        <button
          v-if="can.create"
          @click="openAddModal()"
          class="text-white font-medium cursor-pointer transition-colors duration-150"
          style="background:#D97757;font-size:13px;padding:8px 16px;border-radius:8px;border:none"
          @mouseover="$event.target.style.background='#c4684a'"
          @mouseleave="$event.target.style.background='#D97757'"
        >+ Add Task</button>
      </div>
    </header>

    <!-- BOARD TAB -->
    <div v-if="activeTab === 'board'" class="flex overflow-x-auto overflow-y-hidden flex-1" style="gap:20px;padding:20px;padding-bottom:24px">
      <div
        v-for="status in statuses" :key="status.id"
        class="flex flex-col max-h-full"
        style="background:#F5F4EF;border-radius:12px;min-width:320px;max-width:320px"
        @dragover.prevent="dragOverStatus = status.slug"
        @drop="onDrop(status.slug)"
      >
        <div class="flex items-center justify-between" style="padding:14px 16px 10px">
          <div class="flex items-center" style="gap:8px">
            <span class="font-bold uppercase" style="font-size:11px;letter-spacing:0.06em;color:#5c5c5a">
              {{ status.icon ? status.icon + ' ' : '' }}{{ status.name }}
            </span>
            <span class="font-medium" style="background:white;color:#8c8c8a;font-size:11px;padding:2px 8px;border-radius:9999px">
              {{ tasksByStatus(status.slug).length }}
            </span>
          </div>
        </div>
        <div
          class="flex-1 overflow-y-auto"
          style="padding:0 10px 10px;display:flex;flex-direction:column;gap:8px"
          :style="dragOverStatus === status.slug ? 'background:rgba(217,119,87,0.05);border-radius:8px' : ''"
        >
          <TaskCard
            v-for="task in tasksByStatus(status.slug)"
            :key="task.id"
            :task="task"
            @click="openViewer(task)"
            @dragstart="onDragStart(task)"
            @dragend="dragOverStatus = null"
          />
          <button
            v-if="can.create"
            @click="openAddModal(status.slug)"
            class="w-full font-medium cursor-pointer transition-colors duration-150"
            style="border:2px dashed #e5e4df;color:#8c8c8a;font-size:13px;padding:10px;border-radius:8px;background:transparent;margin-top:4px"
            @mouseover="e => { e.target.style.borderColor='#D97757'; e.target.style.color='#D97757' }"
            @mouseleave="e => { e.target.style.borderColor='#e5e4df'; e.target.style.color='#8c8c8a' }"
          >+ Add task</button>
        </div>
      </div>
    </div>

    <!-- PROJECTS TAB -->
    <div v-if="activeTab === 'projects'" class="grid overflow-y-auto flex-1 content-start" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;padding:20px">
      <ProjectCard
        v-for="p in projects" :key="p.id"
        :project="p"
        :tasks="tasks"
        @click="goToProjectBoard(p.id)"
      />
    </div>

    <!-- TEAM TAB — users with seeTeam permission -->
    <div v-if="activeTab === 'team' && can.seeTeam" class="grid overflow-y-auto flex-1 content-start" style="grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;padding:20px">
      <TeamCard
        v-for="m in team" :key="m.id"
        :member="m"
        :tasks="tasks.filter(t => t.assignee?.id === m.id && t.status !== 'done')"
      />
    </div>

    <!-- CARD VIEWER -->
    <CardViewer
      v-if="viewingTask"
      :task="viewingTask"
      :projects="projects"
      :team="team"
      :statuses="statuses"
      :can="can"
      @close="viewingTask = null"
      @update="handleUpdate"
      @delete="handleDelete"
    />

    <!-- ADD TASK MODAL -->
    <AddTaskModal
      v-if="showAddModal"
      :projects="projects"
      :team="team"
      :statuses="statuses"
      :default-status="addModalStatus"
      :can="can"
      @close="showAddModal = false"
      @saved="showAddModal = false"
    />

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import TaskCard     from '@/Components/WarRoom/TaskCard.vue'
import ProjectCard  from '@/Components/WarRoom/ProjectCard.vue'
import TeamCard     from '@/Components/WarRoom/TeamCard.vue'
import CardViewer   from '@/Components/WarRoom/CardViewer.vue'
import AddTaskModal from '@/Components/WarRoom/AddTaskModal.vue'

const props = defineProps({
  projects: Array,
  tasks:    Array,
  team:     Array,
  statuses: Array,
  can:      { type: Object, default: () => ({}) },
})

const page = usePage()
const auth = computed(() => page.props.auth)

const allTabs = [
  { id: 'board',    label: 'Board' },
  { id: 'projects', label: 'Projects' },
  { id: 'team',     label: 'Team' },
]
const visibleTabs = computed(() => props.can.seeTeam ? allTabs : allTabs.filter(t => t.id !== 'team'))

const activeTab      = ref('board')
const filterProject  = ref('')
const filterAssignee = ref('')
const filterPriority = ref('')
const viewingTask    = ref(null)
const showAddModal   = ref(false)
const addModalStatus = ref(props.statuses?.[0]?.slug ?? 'backlog')
const draggingTask   = ref(null)
const dragOverStatus = ref(null)

const filteredTasks = computed(() =>
  props.tasks.filter(t =>
    (!filterProject.value  || t.project?.id  == filterProject.value) &&
    (!filterAssignee.value || t.assignee?.id == filterAssignee.value) &&
    (!filterPriority.value || t.priority     === filterPriority.value)
  )
)
const tasksByStatus = (slug) => filteredTasks.value.filter(t => t.status === slug)

function openAddModal(slug) { addModalStatus.value = slug ?? props.statuses?.[0]?.slug; showAddModal.value = true }
function openViewer(task)   { viewingTask.value = { ...task } }
function goToProjectBoard(id) { filterProject.value = String(id); activeTab.value = 'board' }

function onDragStart(task) { draggingTask.value = task }
function onDrop(toSlug) {
  if (!draggingTask.value || draggingTask.value.status === toSlug) { dragOverStatus.value = null; return }
  router.patch(route('tasks.update', draggingTask.value.id), { status: toSlug }, { preserveScroll: true })
  draggingTask.value = null
  dragOverStatus.value = null
}

function handleUpdate(data)  { router.patch(route('tasks.update', data.id), data, { preserveScroll: true }) }
function handleDelete(id)    { router.delete(route('tasks.destroy', id), { preserveScroll: true }); viewingTask.value = null }
</script>
