const { Organisasi, Jabatan } = require('../models');

const VALID_TIPE = ['sekretariat', 'aset', 'perbendaharaan', 'akuntansi', 'anggaran'];

// ===================== ORGANISASI =====================

const getAllOrganisasi = async (req, res) => {
  try {
    const { tipe } = req.query;
    const where = {};
    if (tipe) {
      if (!VALID_TIPE.includes(tipe)) {
        return res.status(400).json({
          success: false,
          message: `Tipe organisasi tidak valid. Pilihan: ${VALID_TIPE.join(', ')}`,
        });
      }
      where.tipe = tipe;
    }

    const organisasis = await Organisasi.findAll({
      where,
      include: [{ model: Jabatan, as: 'jabatans', order: [['urutan', 'ASC']] }],
      order: [['urutan', 'ASC']],
    });

    const data = organisasis.map((org) => {
      const o = org.toJSON();
      o.jabatans = o.jabatans.map((j) => {
        if (j.tugas_fungsi) {
          try {
            j.tugas_fungsi = JSON.parse(j.tugas_fungsi);
          } catch {
            // keep as string
          }
        }
        return j;
      });
      return o;
    });

    return res.status(200).json({
      success: true,
      message: 'Berhasil mendapatkan data organisasi.',
      data,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const getOrganisasiById = async (req, res) => {
  try {
    const { id } = req.params;
    const organisasi = await Organisasi.findByPk(id, {
      include: [{ model: Jabatan, as: 'jabatans', order: [['urutan', 'ASC']] }],
    });
    if (!organisasi) {
      return res.status(404).json({ success: false, message: 'Organisasi tidak ditemukan.' });
    }
    const data = organisasi.toJSON();
    data.jabatans = data.jabatans.map((j) => {
      if (j.tugas_fungsi) {
        try {
          j.tugas_fungsi = JSON.parse(j.tugas_fungsi);
        } catch {
          // keep as string
        }
      }
      return j;
    });
    return res.status(200).json({ success: true, data });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const getOrganisasiByTipe = async (req, res) => {
  try {
    const { tipe } = req.params;
    if (!VALID_TIPE.includes(tipe)) {
      return res.status(400).json({
        success: false,
        message: `Tipe organisasi tidak valid. Pilihan: ${VALID_TIPE.join(', ')}`,
      });
    }

    const organisasis = await Organisasi.findAll({
      where: { tipe },
      include: [{ model: Jabatan, as: 'jabatans', order: [['urutan', 'ASC']] }],
      order: [['urutan', 'ASC']],
    });

    const data = organisasis.map((org) => {
      const o = org.toJSON();
      o.jabatans = o.jabatans.map((j) => {
        if (j.tugas_fungsi) {
          try {
            j.tugas_fungsi = JSON.parse(j.tugas_fungsi);
          } catch {
            // keep as string
          }
        }
        return j;
      });
      return o;
    });

    return res.status(200).json({
      success: true,
      message: `Berhasil mendapatkan data organisasi tipe ${tipe}.`,
      data,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const createOrganisasi = async (req, res) => {
  try {
    const { nama, tipe, deskripsi, urutan } = req.body;

    if (!nama || !tipe) {
      return res.status(400).json({ success: false, message: 'Nama dan tipe wajib diisi.' });
    }
    if (!VALID_TIPE.includes(tipe)) {
      return res.status(400).json({
        success: false,
        message: `Tipe tidak valid. Pilihan: ${VALID_TIPE.join(', ')}`,
      });
    }

    const organisasi = await Organisasi.create({
      nama,
      tipe,
      deskripsi,
      urutan: urutan ? parseInt(urutan) : 0,
    });

    return res.status(201).json({
      success: true,
      message: 'Organisasi berhasil ditambahkan.',
      data: organisasi,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const updateOrganisasi = async (req, res) => {
  try {
    const { id } = req.params;
    const organisasi = await Organisasi.findByPk(id);
    if (!organisasi) {
      return res.status(404).json({ success: false, message: 'Organisasi tidak ditemukan.' });
    }

    const { nama, tipe, deskripsi, urutan } = req.body;
    const updateData = {};

    if (nama !== undefined) updateData.nama = nama;
    if (tipe !== undefined) {
      if (!VALID_TIPE.includes(tipe)) {
        return res.status(400).json({
          success: false,
          message: `Tipe tidak valid. Pilihan: ${VALID_TIPE.join(', ')}`,
        });
      }
      updateData.tipe = tipe;
    }
    if (deskripsi !== undefined) updateData.deskripsi = deskripsi;
    if (urutan !== undefined) updateData.urutan = parseInt(urutan);

    await organisasi.update(updateData);

    return res.status(200).json({
      success: true,
      message: 'Organisasi berhasil diperbarui.',
      data: organisasi,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const deleteOrganisasi = async (req, res) => {
  try {
    const { id } = req.params;
    const organisasi = await Organisasi.findByPk(id);
    if (!organisasi) {
      return res.status(404).json({ success: false, message: 'Organisasi tidak ditemukan.' });
    }
    await organisasi.destroy();
    return res.status(200).json({ success: true, message: 'Organisasi berhasil dihapus.' });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

// ===================== JABATAN =====================

const getJabatanByOrganisasi = async (req, res) => {
  try {
    const { organisasi_id } = req.params;
    const jabatans = await Jabatan.findAll({
      where: { organisasi_id },
      order: [['urutan', 'ASC']],
    });
    const data = jabatans.map((j) => {
      const item = j.toJSON();
      if (item.tugas_fungsi) {
        try {
          item.tugas_fungsi = JSON.parse(item.tugas_fungsi);
        } catch {
          // keep as string
        }
      }
      return item;
    });
    return res.status(200).json({ success: true, data });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const createJabatan = async (req, res) => {
  try {
    const { organisasi_id, nama_jabatan, nama_pejabat, nip, tugas_fungsi, urutan } = req.body;

    if (!organisasi_id || !nama_jabatan || !nama_pejabat) {
      return res.status(400).json({
        success: false,
        message: 'organisasi_id, nama_jabatan, dan nama_pejabat wajib diisi.',
      });
    }

    const organisasi = await Organisasi.findByPk(organisasi_id);
    if (!organisasi) {
      return res.status(404).json({ success: false, message: 'Organisasi tidak ditemukan.' });
    }

    const jabatan = await Jabatan.create({
      organisasi_id,
      nama_jabatan,
      nama_pejabat,
      nip,
      tugas_fungsi: Array.isArray(tugas_fungsi)
        ? JSON.stringify(tugas_fungsi)
        : tugas_fungsi,
      urutan: urutan ? parseInt(urutan) : 0,
    });

    const data = jabatan.toJSON();
    if (data.tugas_fungsi) {
      try {
        data.tugas_fungsi = JSON.parse(data.tugas_fungsi);
      } catch {
        // keep as string
      }
    }

    return res.status(201).json({
      success: true,
      message: 'Jabatan berhasil ditambahkan.',
      data,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const updateJabatan = async (req, res) => {
  try {
    const { id } = req.params;
    const jabatan = await Jabatan.findByPk(id);
    if (!jabatan) {
      return res.status(404).json({ success: false, message: 'Jabatan tidak ditemukan.' });
    }

    const { nama_jabatan, nama_pejabat, nip, tugas_fungsi, urutan } = req.body;
    const updateData = {};

    if (nama_jabatan !== undefined) updateData.nama_jabatan = nama_jabatan;
    if (nama_pejabat !== undefined) updateData.nama_pejabat = nama_pejabat;
    if (nip !== undefined) updateData.nip = nip;
    if (urutan !== undefined) updateData.urutan = parseInt(urutan);
    if (tugas_fungsi !== undefined) {
      updateData.tugas_fungsi = Array.isArray(tugas_fungsi)
        ? JSON.stringify(tugas_fungsi)
        : tugas_fungsi;
    }

    await jabatan.update(updateData);

    const data = jabatan.toJSON();
    if (data.tugas_fungsi) {
      try {
        data.tugas_fungsi = JSON.parse(data.tugas_fungsi);
      } catch {
        // keep as string
      }
    }

    return res.status(200).json({
      success: true,
      message: 'Jabatan berhasil diperbarui.',
      data,
    });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

const deleteJabatan = async (req, res) => {
  try {
    const { id } = req.params;
    const jabatan = await Jabatan.findByPk(id);
    if (!jabatan) {
      return res.status(404).json({ success: false, message: 'Jabatan tidak ditemukan.' });
    }
    await jabatan.destroy();
    return res.status(200).json({ success: true, message: 'Jabatan berhasil dihapus.' });
  } catch (error) {
    return res.status(500).json({
      success: false,
      message: 'Terjadi kesalahan pada server.',
      error: error.message,
    });
  }
};

module.exports = {
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
};
