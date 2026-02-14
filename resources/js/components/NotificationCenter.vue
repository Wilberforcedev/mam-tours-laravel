<template>
  <div class="notification-center">
    <!-- Notification Bell -->
    <div class="relative">
      <button 
        @click="toggleNotifications"
        class="relative p-2 text-gray-600 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        
        <!-- Notification Badge -->
        <span 
          v-if="unreadCount > 0"
          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"
        >
          {{ unreadCount > 99 ? '99+' : unreadCount }}
        </span>
      </button>

      <!-- Notification Dropdown -->
      <div 
        v-if="showNotifications"
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border z-50"
      >
        <div class="p-4 border-b">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Notifications</h3>
            <button 
              v-if="unreadCount > 0"
              @click="markAllAsRead"
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              Mark all read
            </button>
          </div>
        </div>

        <div class="max-h-96 overflow-y-auto">
          <div v-if="notifications.length === 0" class="p-4 text-center text-gray-500">
            No notifications yet
          </div>
          
          <div 
            v-for="notification in notifications" 
            :key="notification.id"
            class="p-4 border-b hover:bg-gray-50 cursor-pointer"
            :class="{ 'bg-blue-50': !notification.read_at }"
            @click="markAsRead(notification)"
          >
            <div class="flex items-start">
              <div class="flex-shrink-0 mr-3">
                <div 
                  class="w-8 h-8 rounded-full flex items-center justify-center"
                  :class="getNotificationIconClass(notification.type)"
                >
                  <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path v-if="notification.type.includes('payment')" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"></path>
                    <path v-else-if="notification.type.includes('booking')" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    <path v-else d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"></path>
                  </svg>
                </div>
              </div>
              
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">
                  {{ notification.subject }}
                </p>
                <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                  {{ notification.message }}
                </p>
                <p class="text-xs text-gray-400 mt-2">
                  {{ formatDate(notification.created_at) }}
                </p>
              </div>
              
              <div v-if="!notification.read_at" class="flex-shrink-0 ml-2">
                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="p-4 border-t">
          <button 
            @click="viewAllNotifications"
            class="w-full text-center text-sm text-blue-600 hover:text-blue-800"
          >
            View all notifications
          </button>
        </div>
      </div>
    </div>

    <!-- Click outside to close -->
    <div 
      v-if="showNotifications"
      class="fixed inset-0 z-40"
      @click="showNotifications = false"
    ></div>
  </div>
</template>

<script>
export default {
  name: 'NotificationCenter',
  props: {
    userId: {
      type: [String, Number],
      required: true
    }
  },
  data() {
    return {
      showNotifications: false,
      notifications: [],
      loading: false,
      pollInterval: null
    }
  },
  computed: {
    unreadCount() {
      return this.notifications.filter(n => !n.read_at).length;
    }
  },
  mounted() {
    this.fetchNotifications();
    this.startPolling();
  },
  beforeUnmount() {
    this.stopPolling();
  },
  methods: {
    async fetchNotifications() {
      if (this.loading) return;
      
      this.loading = true;
      try {
        const response = await axios.get('/api/notifications');
        this.notifications = response.data.notifications || [];
      } catch (error) {
        console.error('Failed to fetch notifications:', error);
      } finally {
        this.loading = false;
      }
    },
    
    async markAsRead(notification) {
      if (notification.read_at) return;
      
      try {
        await axios.put(`/api/notifications/${notification.id}/read`);
        notification.read_at = new Date().toISOString();
      } catch (error) {
        console.error('Failed to mark notification as read:', error);
      }
    },
    
    async markAllAsRead() {
      try {
        await axios.put('/api/notifications/mark-all-read');
        this.notifications.forEach(n => {
          if (!n.read_at) {
            n.read_at = new Date().toISOString();
          }
        });
      } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
      }
    },
    
    toggleNotifications() {
      this.showNotifications = !this.showNotifications;
      if (this.showNotifications) {
        this.fetchNotifications();
      }
    },
    
    viewAllNotifications() {
      window.location.href = '/notifications';
    },
    
    getNotificationIconClass(type) {
      if (type.includes('payment')) {
        return 'bg-green-100 text-green-600';
      } else if (type.includes('booking')) {
        return 'bg-blue-100 text-blue-600';
      } else if (type.includes('kyc')) {
        return 'bg-yellow-100 text-yellow-600';
      } else if (type.includes('overdue') || type.includes('reminder')) {
        return 'bg-red-100 text-red-600';
      }
      return 'bg-gray-100 text-gray-600';
    },
    
    formatDate(dateString) {
      const date = new Date(dateString);
      const now = new Date();
      const diffInMinutes = Math.floor((now - date) / (1000 * 60));
      
      if (diffInMinutes < 1) {
        return 'Just now';
      } else if (diffInMinutes < 60) {
        return `${diffInMinutes}m ago`;
      } else if (diffInMinutes < 1440) {
        return `${Math.floor(diffInMinutes / 60)}h ago`;
      } else if (diffInMinutes < 10080) {
        return `${Math.floor(diffInMinutes / 1440)}d ago`;
      } else {
        return date.toLocaleDateString();
      }
    },
    
    startPolling() {
      // Poll for new notifications every 30 seconds
      this.pollInterval = setInterval(() => {
        if (!this.showNotifications) {
          this.fetchNotifications();
        }
      }, 30000);
    },
    
    stopPolling() {
      if (this.pollInterval) {
        clearInterval(this.pollInterval);
        this.pollInterval = null;
      }
    }
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>