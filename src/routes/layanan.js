const express = require('express');
const router = express.Router();
const {
  getAllLayanan,
  getLayananByTipe,
  getLayananById,
  createLayanan,
  updateLayanan,
  deleteLayanan,
} = require('../controllers/layananController');
const authMiddleware = require('../middleware/auth');
const { uploadLayanan } = require('../middleware/upload');

/**
 * @route   GET /api/layanan
 * @desc    Get all layanan (optional ?tipe=&tahun_apbd=&aktif=true)
 * @access  Public
 */
router.get('/', getAllLayanan);

/**
 * @route   GET /api/layanan/tipe/:tipe
 * @desc    Get layanan by tipe
 *          (penganggaran|penatausahaan|pelaporan_pertanggungjawaban|
 *           perencanaan_evaluasi|perjanjian_kinerja)
 * @access  Public
 */
router.get('/tipe/:tipe', getLayananByTipe);

/**
 * @route   GET /api/layanan/:id
 * @desc    Get layanan by ID
 * @access  Public
 */
router.get('/:id', getLayananById);

/**
 * @route   POST /api/layanan
 * @desc    Upload layanan document (tahun_apbd + file)
 * @access  Private
 */
router.post('/', authMiddleware, uploadLayanan.single('file'), createLayanan);

/**
 * @route   PUT /api/layanan/:id
 * @desc    Update layanan
 * @access  Private
 */
router.put('/:id', authMiddleware, uploadLayanan.single('file'), updateLayanan);

/**
 * @route   DELETE /api/layanan/:id
 * @desc    Delete layanan
 * @access  Private
 */
router.delete('/:id', authMiddleware, deleteLayanan);

module.exports = router;
