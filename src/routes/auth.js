const express = require('express');
const router = express.Router();
const { login, changePassword } = require('../controllers/authController');
const authMiddleware = require('../middleware/auth');

/**
 * @route   POST /api/auth/login
 * @desc    Admin login
 * @access  Public
 */
router.post('/login', login);

/**
 * @route   PUT /api/auth/change-password
 * @desc    Change admin password
 * @access  Private
 */
router.put('/change-password', authMiddleware, changePassword);

module.exports = router;
