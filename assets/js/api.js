/**
 * API Interaction Library
 * FIBECO Bidding System
 * File: api.js
 * 
 * Handles all API calls, authentication, and data fetching
 */

// API Configuration
const API_CONFIG = {
    baseURL: '/fibeco-bidding-system/api',
    endpoints: {
        auth: {
            login: '/auth/login',
            register: '/auth/register',
            logout: '/auth/logout',
            verify: '/auth/verify',
            profile: '/auth/profile',
            changePassword: '/auth/change-password',
            forgotPassword: '/auth/forgot-password',
            resetPassword: '/auth/reset-password',
            users: '/auth/users'
        },
        bidding: {
            public: '/bidding/public',
            publicDetail: '/bidding/public/:id',
            sealed: '/bidding/sealed',
            sealedDetail: '/bidding/sealed/:id',
            summary: '/bidding/public/summary',
            export: '/bidding/export',
            bulkImport: '/bidding/bulk-import'
        },
        procurement: {
            list: '/procurement',
            detail: '/procurement/:id',
            export: '/procurement/export'
        },
        upload: {
            single: '/upload',
            batch: '/upload/batch',
            status: '/upload/status/:id',
            pending: '/upload/pending',
            updateStatus: '/upload/:id/status',
            delete: '/upload/:id',
            stats: '/upload/stats'
        },
        stats: {
            overview: '/stats/overview',
            monthly: '/stats/monthly'
        }
    }
};

// Auth token storage
let authToken = localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token') || null;

// ======================================================
// API Request Core
// ======================================================

/**
 * Make API request
 * @param {string} method - HTTP method
 * @param {string} endpoint - API endpoint
 * @param {object} data - Request body data
 * @param {boolean} requireAuth - Whether authentication is required
 * @returns {Promise} API response
 */
async function apiRequest(method, endpoint, data = null, requireAuth = true) {
    const url = API_CONFIG.baseURL + endpoint;
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
    
    if (requireAuth && authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
    }
    
    const options = {
        method: method,
        headers: headers
    };
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || `API Error: ${response.status}`);
        }
        
        return result;
    } catch (error) {
        console.error('API Request failed:', error);
        throw error;
    }
}

/**
 * Make multipart/form-data request (for file uploads)
 * @param {string} method - HTTP method
 * @param {string} endpoint - API endpoint
 * @param {FormData} formData - Form data
 * @param {boolean} requireAuth - Whether authentication is required
 * @returns {Promise} API response
 */
async function apiFormRequest(method, endpoint, formData, requireAuth = true) {
    const url = API_CONFIG.baseURL + endpoint;
    const headers = {};
    
    if (requireAuth && authToken) {
        headers['Authorization'] = `Bearer ${authToken}`;
    }
    
    const options = {
        method: method,
        headers: headers,
        body: formData
    };
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.message || `API Error: ${response.status}`);
        }
        
        return result;
    } catch (error) {
        console.error('API Form Request failed:', error);
        throw error;
    }
}

// ======================================================
// Authentication API
// ======================================================

const AuthAPI = {
    /**
     * Login user
     * @param {string} username - Username
     * @param {string} password - Password
     * @returns {Promise} Login result
     */
    login: async (username, password) => {
        const result = await apiRequest('POST', API_CONFIG.endpoints.auth.login, { username, password }, false);
        if (result.success && result.data.token) {
            authToken = result.data.token;
            localStorage.setItem('auth_token', authToken);
        }
        return result;
    },
    
    /**
     * Register new user
     * @param {object} userData - User registration data
     * @returns {Promise} Registration result
     */
    register: (userData) => apiRequest('POST', API_CONFIG.endpoints.auth.register, userData, false),
    
    /**
     * Logout user
     * @returns {Promise} Logout result
     */
    logout: async () => {
        const result = await apiRequest('POST', API_CONFIG.endpoints.auth.logout, null, true);
        authToken = null;
        localStorage.removeItem('auth_token');
        sessionStorage.removeItem('auth_token');
        return result;
    },
    
    /**
     * Verify current token
     * @returns {Promise} Verification result
     */
    verify: () => apiRequest('GET', API_CONFIG.endpoints.auth.verify, null, true),
    
    /**
     * Get user profile
     * @returns {Promise} User profile
     */
    getProfile: () => apiRequest('GET', API_CONFIG.endpoints.auth.profile, null, true),
    
    /**
     * Update user profile
     * @param {object} profileData - Profile data to update
     * @returns {Promise} Update result
     */
    updateProfile: (profileData) => apiRequest('PUT', API_CONFIG.endpoints.auth.profile, profileData, true),
    
    /**
     * Change password
     * @param {string} currentPassword - Current password
     * @param {string} newPassword - New password
     * @param {string} confirmPassword - Confirm new password
     * @returns {Promise} Change result
     */
    changePassword: (currentPassword, newPassword, confirmPassword) => 
        apiRequest('POST', API_CONFIG.endpoints.auth.changePassword, { currentPassword, newPassword, confirmPassword }, true),
    
    /**
     * Request password reset
     * @param {string} email - User email
     * @returns {Promise} Request result
     */
    forgotPassword: (email) => apiRequest('POST', API_CONFIG.endpoints.auth.forgotPassword, { email }, false),
    
    /**
     * Reset password with token
     * @param {string} token - Reset token
     * @param {string} newPassword - New password
     * @param {string} confirmPassword - Confirm password
     * @returns {Promise} Reset result
     */
    resetPassword: (token, newPassword, confirmPassword) => 
        apiRequest('POST', API_CONFIG.endpoints.auth.resetPassword, { token, newPassword, confirmPassword }, false),
    
    /**
     * Get all users (admin only)
     * @param {number} page - Page number
     * @param {number} limit - Items per page
     * @param {string} search - Search query
     * @returns {Promise} Users list
     */
    getUsers: (page = 1, limit = 20, search = '') => 
        apiRequest('GET', `${API_CONFIG.endpoints.auth.users}?page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`, null, true),
    
    /**
     * Create user (admin only)
     * @param {object} userData - User data
     * @returns {Promise} Creation result
     */
    createUser: (userData) => apiRequest('POST', API_CONFIG.endpoints.auth.users, userData, true),
    
    /**
     * Update user (admin only)
     * @param {number} userId - User ID
     * @param {object} userData - User data to update
     * @returns {Promise} Update result
     */
    updateUser: (userId, userData) => apiRequest('PUT', `${API_CONFIG.endpoints.auth.users}/${userId}`, userData, true),
    
    /**
     * Delete user (admin only)
     * @param {number} userId - User ID
     * @returns {Promise} Delete result
     */
    deleteUser: (userId) => apiRequest('DELETE', `${API_CONFIG.endpoints.auth.users}/${userId}`, null, true),
    
    /**
     * Get auth token
     * @returns {string|null} Auth token
     */
    getToken: () => authToken,
    
    /**
     * Set auth token (for session management)
     * @param {string} token - Auth token
     * @param {boolean} remember - Remember token in localStorage
     */
    setToken: (token, remember = true) => {
        authToken = token;
        if (remember) {
            localStorage.setItem('auth_token', token);
        } else {
            sessionStorage.setItem('auth_token', token);
        }
    },
    
    /**
     * Check if user is authenticated
     * @returns {boolean} Authentication status
     */
    isAuthenticated: () => !!authToken
};

// ======================================================
// Bidding API
// ======================================================

const BiddingAPI = {
    /**
     * Get public bidding list
     * @param {object} params - Query parameters
     * @returns {Promise} Bidding list
     */
    getPublicList: (params = {}) => {
        const queryString = new URLSearchParams(params).toString();
        const url = API_CONFIG.endpoints.bidding.public + (queryString ? `?${queryString}` : '');
        return apiRequest('GET', url, null, false);
    },
    
    /**
     * Get single public bidding record
     * @param {number} id - Record ID
     * @returns {Promise} Bidding record
     */
    getPublicDetail: (id) => {
        const url = API_CONFIG.endpoints.bidding.publicDetail.replace(':id', id);
        return apiRequest('GET', url, null, false);
    },
    
    /**
     * Create public bidding record (admin only)
     * @param {object} data - Bidding data
     * @returns {Promise} Creation result
     */
    createPublic: (data) => apiRequest('POST', API_CONFIG.endpoints.bidding.public, data, true),
    
    /**
     * Update public bidding record (admin only)
     * @param {number} id - Record ID
     * @param {object} data - Bidding data
     * @returns {Promise} Update result
     */
    updatePublic: (id, data) => {
        const url = API_CONFIG.endpoints.bidding.publicDetail.replace(':id', id);
        return apiRequest('PUT', url, data, true);
    },
    
    /**
     * Delete public bidding record (admin only)
     * @param {number} id - Record ID
     * @returns {Promise} Delete result
     */
    deletePublic: (id) => {
        const url = API_CONFIG.endpoints.bidding.publicDetail.replace(':id', id);
        return apiRequest('DELETE', url, null, true);
    },
    
    /**
     * Get sealed bidding list (authenticated)
     * @param {object} params - Query parameters
     * @returns {Promise} Sealed bidding list
     */
    getSealedList: (params = {}) => {
        const queryString = new URLSearchParams(params).toString();
        const url = API_CONFIG.endpoints.bidding.sealed + (queryString ? `?${queryString}` : '');
        return apiRequest('GET', url, null, true);
    },
    
    /**
     * Get single sealed bidding record (authenticated)
     * @param {number} id - Record ID
     * @returns {Promise} Sealed bidding record
     */
    getSealedDetail: (id) => {
        const url = API_CONFIG.endpoints.bidding.sealedDetail.replace(':id', id);
        return apiRequest('GET', url, null, true);
    },
    
    /**
     * Create sealed bidding record (admin only)
     * @param {object} data - Sealed bidding data
     * @returns {Promise} Creation result
     */
    createSealed: (data) => apiRequest('POST', API_CONFIG.endpoints.bidding.sealed, data, true),
    
    /**
     * Update sealed bidding record (admin only)
     * @param {number} id - Record ID
     * @param {object} data - Sealed bidding data
     * @returns {Promise} Update result
     */
    updateSealed: (id, data) => {
        const url = API_CONFIG.endpoints.bidding.sealedDetail.replace(':id', id);
        return apiRequest('PUT', url, data, true);
    },
    
    /**
     * Delete sealed bidding record (admin only)
     * @param {number} id - Record ID
     * @returns {Promise} Delete result
     */
    deleteSealed: (id) => {
        const url = API_CONFIG.endpoints.bidding.sealedDetail.replace(':id', id);
        return apiRequest('DELETE', url, null, true);
    },
    
    /**
     * Get bidding summary statistics
     * @param {number} year - Year for summary
     * @returns {Promise} Summary statistics
     */
    getSummary: (year = new Date().getFullYear()) => {
        return apiRequest('GET', `${API_CONFIG.endpoints.bidding.summary}?year=${year}`, null, false);
    },
    
    /**
     * Export bidding data to CSV
     * @param {string} type - 'public' or 'sealed'
     * @param {string} startDate - Start date
     * @param {string} endDate - End date
     * @returns {string} Download URL
     */
    getExportUrl: (type = 'public', startDate = '', endDate = '') => {
        let url = `${API_CONFIG.baseURL}${API_CONFIG.endpoints.bidding.export}?type=${type}`;
        if (startDate) url += `&start_date=${startDate}`;
        if (endDate) url += `&end_date=${endDate}`;
        return url;
    },
    
    /**
     * Bulk import bidding records (admin only)
     * @param {array} records - Array of records to import
     * @param {string} type - 'public' or 'sealed'
     * @returns {Promise} Import result
     */
    bulkImport: (records, type = 'public') => {
        return apiRequest('POST', API_CONFIG.endpoints.bidding.bulkImport, { records, type }, true);
    }
};

// ======================================================
// Upload API
// ======================================================

const UploadAPI = {
    /**
     * Upload single file
     * @param {File} file - File to upload
     * @param {string} documentType - Document type
     * @param {boolean} autoImport - Auto-import after extraction
     * @returns {Promise} Upload result
     */
    uploadFile: async (file, documentType, autoImport = false) => {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('document_type', documentType);
        formData.append('auto_import', autoImport ? '1' : '0');
        
        return apiFormRequest('POST', API_CONFIG.endpoints.upload.single, formData, true);
    },
    
    /**
     * Upload multiple files
     * @param {FileList} files - List of files to upload
     * @param {string} documentType - Document type
     * @param {boolean} autoImport - Auto-import after extraction
     * @returns {Promise} Upload result
     */
    uploadBatch: async (files, documentType, autoImport = false) => {
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        formData.append('document_type', documentType);
        formData.append('auto_import', autoImport ? '1' : '0');
        
        return apiFormRequest('POST', API_CONFIG.endpoints.upload.batch, formData, true);
    },
    
    /**
     * Get upload status
     * @param {number} documentId - Document ID
     * @returns {Promise} Upload status
     */
    getStatus: (documentId) => {
        const url = API_CONFIG.endpoints.upload.status.replace(':id', documentId);
        return apiRequest('GET', url, null, true);
    },
    
    /**
     * Get pending uploads
     * @param {string} type - Document type filter
     * @param {number} limit - Maximum records
     * @returns {Promise} Pending uploads list
     */
    getPending: (type = '', limit = 20) => {
        let url = API_CONFIG.endpoints.upload.pending;
        const params = [];
        if (type) params.push(`type=${type}`);
        params.push(`limit=${limit}`);
        if (params.length) url += `?${params.join('&')}`;
        return apiRequest('GET', url, null, true);
    },
    
    /**
     * Update upload status (approve/reject)
     * @param {number} documentId - Document ID
     * @param {string} status - New status
     * @param {number} recordId - Linked record ID (if approved)
     * @param {string} errorMessage - Error message (if rejected)
     * @returns {Promise} Update result
     */
    updateStatus: (documentId, status, recordId = null, errorMessage = null) => {
        const url = API_CONFIG.endpoints.upload.updateStatus.replace(':id', documentId);
        const data = { status };
        if (recordId) data.record_id = recordId;
        if (errorMessage) data.error_message = errorMessage;
        return apiRequest('PUT', url, data, true);
    },
    
    /**
     * Delete uploaded file
     * @param {number} documentId - Document ID
     * @returns {Promise} Delete result
     */
    deleteUpload: (documentId) => {
        const url = API_CONFIG.endpoints.upload.delete.replace(':id', documentId);
        return apiRequest('DELETE', url, null, true);
    },
    
    /**
     * Get upload statistics
     * @returns {Promise} Statistics
     */
    getStats: () => apiRequest('GET', API_CONFIG.endpoints.upload.stats, null, true)
};

// ======================================================
// Statistics API
// ======================================================

const StatsAPI = {
    /**
     * Get overview statistics
     * @returns {Promise} Overview stats
     */
    getOverview: () => apiRequest('GET', API_CONFIG.endpoints.stats.overview, null, true),
    
    /**
     * Get monthly statistics
     * @param {number} year - Year
     * @returns {Promise} Monthly stats
     */
    getMonthly: (year = new Date().getFullYear()) => {
        return apiRequest('GET', `${API_CONFIG.endpoints.stats.monthly}?year=${year}`, null, true);
    }
};

// ======================================================
// Procurement API
// ======================================================

const ProcurementAPI = {
    /**
     * Get procurement list
     * @param {object} params - Query parameters
     * @returns {Promise} Procurement list
     */
    getList: (params = {}) => {
        const queryString = new URLSearchParams(params).toString();
        const url = API_CONFIG.endpoints.procurement.list + (queryString ? `?${queryString}` : '');
        return apiRequest('GET', url, null, true);
    },
    
    /**
     * Get single procurement record
     * @param {number} id - Record ID
     * @returns {Promise} Procurement record
     */
    getDetail: (id) => {
        const url = API_CONFIG.endpoints.procurement.detail.replace(':id', id);
        return apiRequest('GET', url, null, true);
    },
    
    /**
     * Create procurement record (admin only)
     * @param {object} data - Procurement data
     * @returns {Promise} Creation result
     */
    create: (data) => apiRequest('POST', API_CONFIG.endpoints.procurement.list, data, true),
    
    /**
     * Update procurement record (admin only)
     * @param {number} id - Record ID
     * @param {object} data - Procurement data
     * @returns {Promise} Update result
     */
    update: (id, data) => {
        const url = API_CONFIG.endpoints.procurement.detail.replace(':id', id);
        return apiRequest('PUT', url, data, true);
    },
    
    /**
     * Delete procurement record (admin only)
     * @param {number} id - Record ID
     * @returns {Promise} Delete result
     */
    delete: (id) => {
        const url = API_CONFIG.endpoints.procurement.detail.replace(':id', id);
        return apiRequest('DELETE', url, null, true);
    },
    
    /**
     * Get export URL
     * @returns {string} Export URL
     */
    getExportUrl: () => `${API_CONFIG.baseURL}${API_CONFIG.endpoints.procurement.export}`
};

// ======================================================
// Export all API modules
// ======================================================
const FIBECOAPI = {
    auth: AuthAPI,
    bidding: BiddingAPI,
    upload: UploadAPI,
    stats: StatsAPI,
    procurement: ProcurementAPI,
    
    // Utility
    setToken: AuthAPI.setToken,
    getToken: AuthAPI.getToken,
    isAuthenticated: AuthAPI.isAuthenticated
};

// Make available globally
window.FIBECOAPI = FIBECOAPI;

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FIBECOAPI;
}