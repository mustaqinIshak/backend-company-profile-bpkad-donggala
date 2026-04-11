const express = require('express');
const router = express.Router();
const {
  getAllOrganisasi,
  getOrganisasiById,
  getOrganisasiByTipe,
  createOrganisasi,
  updateOrganisasi,
  deleteOrganisasi,
  getJabatanByOrganisasi,
  createJabatan,
  updateJabatan,
  deleteJabatan,
} = require('../controllers/organisasiController');
const authMiddleware = require('../middleware/auth');

// ===================== ORGANISASI ROUTES =====================

/**
 * @route   GET /api/organisasi
 * @desc    Get all organisasi (optional ?tipe=sekretariat filter)
 * @access  Public
 */
router.get('/', getAllOrganisasi);

/**
 * @route   GET /api/organisasi/tipe/:tipe
 * @desc    Get organisasi by tipe (sekretariat|aset|perbendaharaan|akuntansi|anggaran)
 * @access  Public
 */
router.get('/tipe/:tipe', getOrganisasiByTipe);

/**
 * @route   GET /api/organisasi/:id
 * @desc    Get organisasi by ID
 * @access  Public
 */
router.get('/:id', getOrganisasiById);

/**
 * @route   POST /api/organisasi
 * @desc    Create organisasi
 * @access  Private
 */
router.post('/', authMiddleware, createOrganisasi);

/**
 * @route   PUT /api/organisasi/:id
 * @desc    Update organisasi
 * @access  Private
 */
router.put('/:id', authMiddleware, updateOrganisasi);

/**
 * @route   DELETE /api/organisasi/:id
 * @desc    Delete organisasi
 * @access  Private
 */
router.delete('/:id', authMiddleware, deleteOrganisasi);

// ===================== JABATAN ROUTES =====================

/**
 * @route   GET /api/organisasi/:organisasi_id/jabatan
 * @desc    Get all jabatan in an organisasi
 * @access  Public
 */
router.get('/:organisasi_id/jabatan', getJabatanByOrganisasi);

/**
 * @route   POST /api/organisasi/jabatan
 * @desc    Create jabatan
 * @access  Private
 */
router.post('/jabatan', authMiddleware, createJabatan);

/**
 * @route   PUT /api/organisasi/jabatan/:id
 * @desc    Update jabatan
 * @access  Private
 */
router.put('/jabatan/:id', authMiddleware, updateJabatan);

/**
 * @route   DELETE /api/organisasi/jabatan/:id
 * @desc    Delete jabatan
 * @access  Private
 */
router.delete('/jabatan/:id', authMiddleware, deleteJabatan);

module.exports = router;
