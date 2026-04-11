const path = require('path');
const fs = require('fs');
const { Berita } = require('../models');

const generateSlug = (judul) => {
  return judul
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/\s+/g, '-')
    .replace(/-+/g, '-')
    .trim()
    + '-' + Date.now();
};

const getAllBerita = async (req, res) => {
  try {
    const { kategori, aktif, page = 1, limit = 10 } = req.query;
    const where = {};
    if (kategori) where.kategori = kategori;
    if (aktif !== undefined) where.aktif = aktif === 'true';

    const offset = (parseInt(page) - 1) * parseInt(limit);
    const { count, rows } = await Berita.findAndCountAll({
      where,
      order: [['tanggal_publish', 'DESC'], ['createdAt', 'DESC']],
      limit: parseInt(limit),
      offset,
    });

    return res.status(200).json({
      success: true,
      message: 'Berhasil mendapatkan data berita.',
      data: rows,
      pagination: {
        total: count,
        page: parseInt(page),
        limit: parseInt(limit),
        totalPages: Math.ceil(count / parseInt(limit)),
      },
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const getBeritaBySlug = async (req, res) => {
  try {
    const { slug } = req.params;
    const berita = await Berita.findOne({ where: { slug } });
    if (!berita) {
      return res.status(404).json({ success: false, message: 'Berita tidak ditemukan.' });
    }
    return res.status(200).json({ success: true, data: berita });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const getBeritaById = async (req, res) => {
  try {
    const { id } = req.params;
    const berita = await Berita.findByPk(id);
    if (!berita) {
      return res.status(404).json({ success: false, message: 'Berita tidak ditemukan.' });
    }
    return res.status(200).json({ success: true, data: berita });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const createBerita = async (req, res) => {
  try {
    const { judul, isi, kategori, tanggal_publish, aktif } = req.body;

    if (!judul || !isi) {
      return res.status(400).json({ success: false, message: 'Judul dan isi wajib diisi.' });
    }

    const slug = generateSlug(judul);
    const gambar = req.file ? path.join('berita', req.file.filename) : null;

    const berita = await Berita.create({
      judul,
      slug,
      isi,
      gambar,
      kategori: kategori || 'Kegiatan',
      tanggal_publish: tanggal_publish || new Date().toISOString().split('T')[0],
      aktif: aktif !== undefined ? aktif === 'true' || aktif === true : true,
    });

    return res.status(201).json({
      success: true,
      message: 'Berita berhasil ditambahkan.',
      data: berita,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const updateBerita = async (req, res) => {
  try {
    const { id } = req.params;
    const berita = await Berita.findByPk(id);
    if (!berita) {
      return res.status(404).json({ success: false, message: 'Berita tidak ditemukan.' });
    }

    const { judul, isi, kategori, tanggal_publish, aktif } = req.body;
    const updateData = {};

    if (judul !== undefined) updateData.judul = judul;
    if (isi !== undefined) updateData.isi = isi;
    if (kategori !== undefined) updateData.kategori = kategori;
    if (tanggal_publish !== undefined) updateData.tanggal_publish = tanggal_publish;
    if (aktif !== undefined) updateData.aktif = aktif === 'true' || aktif === true;

    if (req.file) {
      if (berita.gambar) {
        const oldPath = path.join(process.env.UPLOAD_PATH || './uploads', berita.gambar);
        if (fs.existsSync(oldPath)) fs.unlinkSync(oldPath);
      }
      updateData.gambar = path.join('berita', req.file.filename);
    }

    await berita.update(updateData);

    return res.status(200).json({
      success: true,
      message: 'Berita berhasil diperbarui.',
      data: berita,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const deleteBerita = async (req, res) => {
  try {
    const { id } = req.params;
    const berita = await Berita.findByPk(id);
    if (!berita) {
      return res.status(404).json({ success: false, message: 'Berita tidak ditemukan.' });
    }

    if (berita.gambar) {
      const filePath = path.join(process.env.UPLOAD_PATH || './uploads', berita.gambar);
      if (fs.existsSync(filePath)) fs.unlinkSync(filePath);
    }

    await berita.destroy();
    return res.status(200).json({ success: true, message: 'Berita berhasil dihapus.' });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

module.exports = {
  getAllBerita,
  getBeritaBySlug,
  getBeritaById,
  createBerita,
  updateBerita,
  deleteBerita,
};
