import api from './api';
import { getUserRole } from './auth';

const canUseNotifications = () => ['admin', 'teacher', 'education'].includes(getUserRole());

export const notificationService = {
  async getNotifications() {
    if (!canUseNotifications()) return [];

    try {
      const response = await api.get('/notifications');
      return response.data;
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
      throw error;
    }
  },

  async markAsRead(notificationId) {
    if (!canUseNotifications()) return null;

    try {
      const response = await api.post(`/notifications/${notificationId}/read`);
      return response.data;
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
      throw error;
    }
  },

  async markAllAsRead() {
    if (!canUseNotifications()) return null;

    try {
      const response = await api.post('/notifications/mark-all-read');
      return response.data;
    } catch (error) {
      console.error('Failed to mark all notifications as read:', error);
      throw error;
    }
  },

  async deleteNotification(notificationId) {
    if (!canUseNotifications()) return null;

    try {
      const response = await api.delete(`/notifications/${notificationId}`);
      return response.data;
    } catch (error) {
      console.error('Failed to delete notification:', error);
      throw error;
    }
  }
};
