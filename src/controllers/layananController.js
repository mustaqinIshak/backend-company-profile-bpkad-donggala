const path = require('path');
const fs = require('fs');
const { Layanan } = require('../models');

const VALID_TIPE = [
  'penganggaran',
  'penatausahaan',
  'pelaporan_pertanggungjawaban',
  'perencanaan_evaluasi',
  'perjanjian_kinerja',
];

const UPLOAD_BASE = path.resolve(process.env.UPLOAD_PATH || './uploads');

// Returns an absolute path within the uploads directory, preventing traversal
const safeUploadPath = (subPath) => {
  const resolved = path.resolve(UPLOAD_BASE, subPath);
  if (!resolved.startsWith(UPLOAD_BASE + path.sep) && resolved !== UPLOAD_BASE) {
    throw new Error('Invalid file path');
  }
  return resolved;
};

// Dynamic APBD year validation: allow 1990 to current year + 5
const isValidTahunApbd = (tahun) => {
  const currentYear = new Date().getFullYear();
  return !isNaN(tahun) && tahun >= 1990 && tahun <= currentYear + 5;
};

const getAllLayanan = async (req, res) => {
  try {
    const { tipe, tahun_apbd, aktif } = req.query;
    const where = {};

    if (tipe) {
      if (!VALID_TIPE.includes(tipe)) {
        return res.status(400).json({
          success: false,
          message: `Tipe layanan tidak valid. Pilihan: ${VALID_TIPE.join(', ')}`,
        });
      }
      where.tipe = tipe;
    }
    if (tahun_apbd) where.tahun_apbd = tahun_apbd;
    if (aktif !== undefined) where.aktif = aktif === 'true';

    const layanans = await Layanan.findAll({
      where,
      order: [['tahun_apbd', 'DESC'], ['createdAt', 'DESC']],
    });

    return res.status(200).json({
      success: true,
      message: 'Berhasil mendapatkan data layanan.',
      data: layanans,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const getLayananByTipe = async (req, res) => {
  try {
    const { tipe } = req.params;
    if (!VALID_TIPE.includes(tipe)) {
      return res.status(400).json({
        success: false,
        message: `Tipe layanan tidak valid. Pilihan: ${VALID_TIPE.join(', ')}`,
      });
    }

    const { tahun_apbd, aktif } = req.query;
    const where = { tipe };
    if (tahun_apbd) where.tahun_apbd = tahun_apbd;
    if (aktif !== undefined) where.aktif = aktif === 'true';

    const layanans = await Layanan.findAll({
      where,
      order: [['tahun_apbd', 'DESC'], ['createdAt', 'DESC']],
    });

    return res.status(200).json({
      success: true,
      message: `Berhasil mendapatkan data layanan ${tipe}.`,
      data: layanans,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const getLayananById = async (req, res) => {
  try {
    const { id } = req.params;
    const layanan = await Layanan.findByPk(id);
    if (!layanan) {
      return res.status(404).json({ success: false, message: 'Layanan tidak ditemukan.' });
    }
    return res.status(200).json({ success: true, data: layanan });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const createLayanan = async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ success: false, message: 'File wajib diunggah.' });
    }

    // req.file.path is controlled by multer's diskStorage; resolve it safely
    const uploadedFilePath = safeUploadPath(path.relative(UPLOAD_BASE, path.resolve(req.file.path)));
    const { tipe, judul, tahun_apbd, deskripsi, aktif } = req.body;

    if (!tipe || !judul || !tahun_apbd) {
      // Delete uploaded file if validation fails
      if (fs.existsSync(uploadedFilePath)) fs.unlinkSync(uploadedFilePath);
      return res.status(400).json({
        success: false,
        message: 'Tipe, judul, dan tahun_apbd wajib diisi.',
      });
    }

    if (!VALID_TIPE.includes(tipe)) {
      if (fs.existsSync(uploadedFilePath)) fs.unlinkSync(uploadedFilePath);
      return res.status(400).json({
        success: false,
        message: `Tipe layanan tidak valid. Pilihan: ${VALID_TIPE.join(', ')}`,
      });
    }

    const tahunNum = parseInt(tahun_apbd, 10);
    if (!isValidTahunApbd(tahunNum)) {
      if (fs.existsSync(uploadedFilePath)) fs.unlinkSync(uploadedFilePath);
      return res.status(400).json({
        success: false,
        message: 'Tahun APBD tidak valid.',
      });
    }

    const layanan = await Layanan.create({
      tipe,
      judul,
      tahun_apbd: tahunNum.toString(),
      deskripsi,
      file: path.join('layanan', req.file.filename),
      nama_file_asli: req.file.originalname,
      aktif: aktif !== undefined ? aktif === 'true' || aktif === true : true,
    });

    return res.status(201).json({
      success: true,
      message: 'Layanan berhasil ditambahkan.',
      data: layanan,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const updateLayanan = async (req, res) => {
  try {
    const { id } = req.params;
    const layanan = await Layanan.findByPk(id);
    const newFilePath = req.file
      ? safeUploadPath(path.relative(UPLOAD_BASE, path.resolve(req.file.path)))
      : null;

    if (!layanan) {
      if (newFilePath && fs.existsSync(newFilePath)) fs.unlinkSync(newFilePath);
      return res.status(404).json({ success: false, message: 'Layanan tidak ditemukan.' });
    }

    const { tipe, judul, tahun_apbd, deskripsi, aktif } = req.body;
    const updateData = {};

    if (tipe !== undefined) {
      if (!VALID_TIPE.includes(tipe)) {
        if (newFilePath && fs.existsSync(newFilePath)) fs.unlinkSync(newFilePath);
        return res.status(400).json({
          success: false,
          message: `Tipe layanan tidak valid. Pilihan: ${VALID_TIPE.join(', ')}`,
        });
      }
      updateData.tipe = tipe;
    }
    if (judul !== undefined) updateData.judul = judul;
    if (tahun_apbd !== undefined) {
      const tahunNum = parseInt(tahun_apbd, 10);
      if (!isValidTahunApbd(tahunNum)) {
        if (newFilePath && fs.existsSync(newFilePath)) fs.unlinkSync(newFilePath);
        return res.status(400).json({ success: false, message: 'Tahun APBD tidak valid.' });
      }
      updateData.tahun_apbd = tahunNum.toString();
    }
    if (deskripsi !== undefined) updateData.deskripsi = deskripsi;
    if (aktif !== undefined) updateData.aktif = aktif === 'true' || aktif === true;

    if (req.file) {
      const oldPath = safeUploadPath(layanan.file);
      if (fs.existsSync(oldPath)) fs.unlinkSync(oldPath);
      updateData.file = path.join('layanan', req.file.filename);
      updateData.nama_file_asli = req.file.originalname;
    }

    await layanan.update(updateData);

    return res.status(200).json({
      success: true,
      message: 'Layanan berhasil diperbarui.',
      data: layanan,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const deleteLayanan = async (req, res) => {
  try {
    const { id } = req.params;
    const layanan = await Layanan.findByPk(id);
    if (!layanan) {
      return res.status(404).json({ success: false, message: 'Layanan tidak ditemukan.' });
    }

    const filePath = safeUploadPath(layanan.file);
    if (fs.existsSync(filePath)) fs.unlinkSync(filePath);

    await layanan.destroy();
    return res.status(200).json({ success: true, message: 'Layanan berhasil dihapus.' });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

module.exports = {
  getAllLayanan,
  getLayananByTipe,
  getLayananById,
  createLayanan,
  updateLayanan,
  deleteLayanan,
};
