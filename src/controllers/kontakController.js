const { Kontak } = require('../models');

const createKontak = async (req, res) => {
  try {
    const { nama, email, telepon, subjek, pesan } = req.body;

    if (!nama || !email || !subjek || !pesan) {
      return res.status(400).json({
        success: false,
        message: 'Nama, email, subjek, dan pesan wajib diisi.',
      });
    }

    // Basic email validation (safe regex)
    const emailRegex = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
    if (!emailRegex.test(email)) {
      return res.status(400).json({
        success: false,
        message: 'Format email tidak valid.',
      });
    }

    const kontak = await Kontak.create({ nama, email, telepon, subjek, pesan });

    return res.status(201).json({
      success: true,
      message: 'Pesan berhasil dikirim. Terima kasih telah menghubungi kami.',
      data: {
        id: kontak.id,
        nama: kontak.nama,
        email: kontak.email,
        subjek: kontak.subjek,
        createdAt: kontak.createdAt,
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

const getAllKontak = async (req, res) => {
  try {
    const { status, page = 1, limit = 20 } = req.query;
    const where = {};
    if (status) where.status = status;

    const offset = (parseInt(page) - 1) * parseInt(limit);
    const { count, rows } = await Kontak.findAndCountAll({
      where,
      order: [['createdAt', 'DESC']],
      limit: parseInt(limit),
      offset,
    });

    return res.status(200).json({
      success: true,
      message: 'Berhasil mendapatkan data kontak.',
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

const getKontakById = async (req, res) => {
  try {
    const { id } = req.params;
    const kontak = await Kontak.findByPk(id);
    if (!kontak) {
      return res.status(404).json({ success: false, message: 'Pesan kontak tidak ditemukan.' });
    }

    // Auto mark as read
    if (kontak.status === 'belum_dibaca') {
      await kontak.update({ status: 'sudah_dibaca' });
    }

    return res.status(200).json({ success: true, data: kontak });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const updateStatusKontak = async (req, res) => {
  try {
    const { id } = req.params;
    const { status } = req.body;

    const validStatus = ['belum_dibaca', 'sudah_dibaca', 'dibalas'];
    if (!validStatus.includes(status)) {
      return res.status(400).json({
        success: false,
        message: `Status tidak valid. Pilihan: ${validStatus.join(', ')}`,
      });
    }

    const kontak = await Kontak.findByPk(id);
    if (!kontak) {
      return res.status(404).json({ success: false, message: 'Pesan kontak tidak ditemukan.' });
    }

    await kontak.update({ status });

    return res.status(200).json({
      success: true,
      message: 'Status kontak berhasil diperbarui.',
      data: kontak,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const deleteKontak = async (req, res) => {
  try {
    const { id } = req.params;
    const kontak = await Kontak.findByPk(id);
    if (!kontak) {
      return res.status(404).json({ success: false, message: 'Pesan kontak tidak ditemukan.' });
    }
    await kontak.destroy();
    return res.status(200).json({ success: true, message: 'Pesan kontak berhasil dihapus.' });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

module.exports = {
  createKontak,
  getAllKontak,
  getKontakById,
  updateStatusKontak,
  deleteKontak,
};
