const path = require('path');
const fs = require('fs');
const { Jumbotron } = require('../models');

const getAllJumbotron = async (req, res) => {
  try {
    const { aktif } = req.query;
    const where = {};
    if (aktif !== undefined) {
      where.aktif = aktif === 'true';
    }

    const jumbotrons = await Jumbotron.findAll({
      where,
      order: [['urutan', 'ASC'], ['createdAt', 'DESC']],
    });

    return res.status(200).json({
      success: true,
      message: 'Berhasil mendapatkan data jumbotron.',
      data: jumbotrons,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const getJumbotronById = async (req, res) => {
  try {
    const { id } = req.params;
    const jumbotron = await Jumbotron.findByPk(id);
    if (!jumbotron) {
      return res.status(404).json({ success: false, message: 'Jumbotron tidak ditemukan.' });
    }
    return res.status(200).json({ success: true, data: jumbotron });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const createJumbotron = async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ success: false, message: 'Gambar wajib diunggah.' });
    }

    const { judul, deskripsi, urutan, aktif } = req.body;
    const gambar = path.join('jumbotron', req.file.filename);

    const jumbotron = await Jumbotron.create({
      judul,
      deskripsi,
      gambar,
      urutan: urutan ? parseInt(urutan) : 0,
      aktif: aktif !== undefined ? aktif === 'true' || aktif === true : true,
    });

    return res.status(201).json({
      success: true,
      message: 'Jumbotron berhasil ditambahkan.',
      data: jumbotron,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const updateJumbotron = async (req, res) => {
  try {
    const { id } = req.params;
    const jumbotron = await Jumbotron.findByPk(id);
    if (!jumbotron) {
      return res.status(404).json({ success: false, message: 'Jumbotron tidak ditemukan.' });
    }

    const { judul, deskripsi, urutan, aktif } = req.body;
    const updateData = {};

    if (judul !== undefined) updateData.judul = judul;
    if (deskripsi !== undefined) updateData.deskripsi = deskripsi;
    if (urutan !== undefined) updateData.urutan = parseInt(urutan);
    if (aktif !== undefined) updateData.aktif = aktif === 'true' || aktif === true;

    if (req.file) {
      // Delete old image
      const oldPath = path.join(process.env.UPLOAD_PATH || './uploads', jumbotron.gambar);
      if (fs.existsSync(oldPath)) fs.unlinkSync(oldPath);
      updateData.gambar = path.join('jumbotron', req.file.filename);
    }

    await jumbotron.update(updateData);

    return res.status(200).json({
      success: true,
      message: 'Jumbotron berhasil diperbarui.',
      data: jumbotron,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const deleteJumbotron = async (req, res) => {
  try {
    const { id } = req.params;
    const jumbotron = await Jumbotron.findByPk(id);
    if (!jumbotron) {
      return res.status(404).json({ success: false, message: 'Jumbotron tidak ditemukan.' });
    }

    // Delete image file
    const filePath = path.join(process.env.UPLOAD_PATH || './uploads', jumbotron.gambar);
    if (fs.existsSync(filePath)) fs.unlinkSync(filePath);

    await jumbotron.destroy();

    return res.status(200).json({
      success: true,
      message: 'Jumbotron berhasil dihapus.',
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

module.exports = {
  getAllJumbotron,
  getJumbotronById,
  createJumbotron,
  updateJumbotron,
  deleteJumbotron,
};
