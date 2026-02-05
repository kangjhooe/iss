import { openDB } from 'idb'

const DB_NAME = 'iss-offline-db'
const DB_VERSION = 1

const STORES = {
  STUDENT_ATTENDANCE: 'student-attendance',
  EMPLOYEE_ATTENDANCE: 'employee-attendance',
  SYNC_QUEUE: 'sync-queue'
}

let dbPromise = null

function getDB() {
  if (!dbPromise) {
    dbPromise = openDB(DB_NAME, DB_VERSION, {
      upgrade(db) {
        // Student attendance store
        if (!db.objectStoreNames.contains(STORES.STUDENT_ATTENDANCE)) {
          const studentStore = db.createObjectStore(STORES.STUDENT_ATTENDANCE, { keyPath: 'id', autoIncrement: true })
          studentStore.createIndex('teachingJournalId', 'teachingJournalId')
        }
        // Employee attendance store
        if (!db.objectStoreNames.contains(STORES.EMPLOYEE_ATTENDANCE)) {
          const employeeStore = db.createObjectStore(STORES.EMPLOYEE_ATTENDANCE, { keyPath: 'id', autoIncrement: true })
          employeeStore.createIndex('date', 'date')
        }
        // Sync queue store
        if (!db.objectStoreNames.contains(STORES.SYNC_QUEUE)) {
          const syncStore = db.createObjectStore(STORES.SYNC_QUEUE, { keyPath: 'id', autoIncrement: true })
          syncStore.createIndex('type', 'type')
          syncStore.createIndex('status', 'status')
        }
      }
    })
  }
  return dbPromise
}

/**
 * Student Attendance Offline Storage
 */
export const studentAttendanceStorage = {
  async save(teachingJournalId, attendances, students = null) {
    const db = await getDB()
    const tx = db.transaction(STORES.STUDENT_ATTENDANCE, 'readwrite')
    const store = tx.objectStore(STORES.STUDENT_ATTENDANCE)
    
    // Remove existing data for this journal
    const allData = await store.getAll()
    const existing = allData.filter(item => item.teachingJournalId === teachingJournalId)
    await Promise.all(existing.map(item => store.delete(item.id)))
    
    // Save new data
    const data = {
      teachingJournalId,
      attendances,
      students, // Store students data for offline access
      timestamp: Date.now(),
      synced: false
    }
    await store.add(data)
    await tx.done
    
    // Add to sync queue
    await this.addToSyncQueue('student-attendance', {
      teachingJournalId,
      attendances
    })
    
    return data
  },

  async get(teachingJournalId) {
    const db = await getDB()
    const tx = db.transaction(STORES.STUDENT_ATTENDANCE, 'readonly')
    const store = tx.objectStore(STORES.STUDENT_ATTENDANCE)
    const allData = await store.getAll()
    return allData.find(item => item.teachingJournalId === teachingJournalId)
  },

  async getAll() {
    const db = await getDB()
    const tx = db.transaction(STORES.STUDENT_ATTENDANCE, 'readonly')
    const store = tx.objectStore(STORES.STUDENT_ATTENDANCE)
    return await store.getAll()
  },

  async markSynced(id) {
    const db = await getDB()
    const tx = db.transaction(STORES.STUDENT_ATTENDANCE, 'readwrite')
    const store = tx.objectStore(STORES.STUDENT_ATTENDANCE)
    const data = await store.get(id)
    if (data) {
      data.synced = true
      await store.put(data)
    }
    await tx.done
  },

  async addToSyncQueue(type, data) {
    const db = await getDB()
    const tx = db.transaction(STORES.SYNC_QUEUE, 'readwrite')
    const store = tx.objectStore(STORES.SYNC_QUEUE)
    await store.add({
      type,
      data,
      status: 'pending',
      timestamp: Date.now(),
      retries: 0
    })
  }
}

/**
 * Employee Attendance Offline Storage
 */
export const employeeAttendanceStorage = {
  async save(date, attendances) {
    const db = await getDB()
    const tx = db.transaction(STORES.EMPLOYEE_ATTENDANCE, 'readwrite')
    const store = tx.objectStore(STORES.EMPLOYEE_ATTENDANCE)
    
    // Remove existing data for this date
    const allData = await store.getAll()
    const existing = allData.filter(item => item.date === date)
    await Promise.all(existing.map(item => store.delete(item.id)))
    
    // Save new data
    const data = {
      date,
      attendances,
      timestamp: Date.now(),
      synced: false
    }
    await store.add(data)
    
    // Add to sync queue
    await this.addToSyncQueue('employee-attendance', {
      date,
      attendances
    })
    
    return data
  },

  async get(date) {
    const db = await getDB()
    const tx = db.transaction(STORES.EMPLOYEE_ATTENDANCE, 'readonly')
    const store = tx.objectStore(STORES.EMPLOYEE_ATTENDANCE)
    const allData = await store.getAll()
    return allData.find(item => item.date === date)
  },

  async getAll() {
    const db = await getDB()
    const tx = db.transaction(STORES.EMPLOYEE_ATTENDANCE, 'readonly')
    const store = tx.objectStore(STORES.EMPLOYEE_ATTENDANCE)
    return await store.getAll()
  },

  async markSynced(id) {
    const db = await getDB()
    const tx = db.transaction(STORES.EMPLOYEE_ATTENDANCE, 'readwrite')
    const store = tx.objectStore(STORES.EMPLOYEE_ATTENDANCE)
    const data = await store.get(id)
    if (data) {
      data.synced = true
      await store.put(data)
    }
    await tx.done
  },

  async addToSyncQueue(type, data) {
    const db = await getDB()
    const tx = db.transaction(STORES.SYNC_QUEUE, 'readwrite')
    const store = tx.objectStore(STORES.SYNC_QUEUE)
    
    // Check if similar item already exists
    const allItems = await store.getAll()
    const existing = allItems.find(item => 
      item.type === type && 
      item.status === 'pending' &&
      JSON.stringify(item.data) === JSON.stringify(data)
    )
    
    if (!existing) {
      await store.add({
        type,
        data,
        status: 'pending',
        timestamp: Date.now(),
        retries: 0
      })
    }
    await tx.done
  }
}

/**
 * Sync Queue Management
 */
export const syncQueue = {
  async getAll() {
    const db = await getDB()
    const tx = db.transaction(STORES.SYNC_QUEUE, 'readonly')
    const store = tx.objectStore(STORES.SYNC_QUEUE)
    return await store.getAll()
  },

  async getPending() {
    const db = await getDB()
    const tx = db.transaction(STORES.SYNC_QUEUE, 'readonly')
    const store = tx.objectStore(STORES.SYNC_QUEUE)
    const allData = await store.getAll()
    return allData.filter(item => item.status === 'pending')
  },

  async markSynced(id) {
    const db = await getDB()
    const tx = db.transaction(STORES.SYNC_QUEUE, 'readwrite')
    const store = tx.objectStore(STORES.SYNC_QUEUE)
    const data = await store.get(id)
    if (data) {
      data.status = 'synced'
      await store.put(data)
    }
    await tx.done
  },

  async markFailed(id, error) {
    const db = await getDB()
    const tx = db.transaction(STORES.SYNC_QUEUE, 'readwrite')
    const store = tx.objectStore(STORES.SYNC_QUEUE)
    const data = await store.get(id)
    if (data) {
      data.status = 'failed'
      data.error = error
      data.retries = (data.retries || 0) + 1
      await store.put(data)
    }
    await tx.done
  },

  async remove(id) {
    const db = await getDB()
    const tx = db.transaction(STORES.SYNC_QUEUE, 'readwrite')
    const store = tx.objectStore(STORES.SYNC_QUEUE)
    await store.delete(id)
    await tx.done
  }
}

/**
 * Check if online
 */
export function isOnline() {
  return navigator.onLine
}

/**
 * Network status listener
 */
export function onNetworkStatusChange(callback) {
  window.addEventListener('online', () => callback(true))
  window.addEventListener('offline', () => callback(false))
}
