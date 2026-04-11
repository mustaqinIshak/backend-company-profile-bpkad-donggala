const path = require('path');
const fs = require('fs');
const { Profile } = require('../models');

const getProfile = async (req, res) => {
  try {
    let profile = await Profile.findOne();
    if (!profile) {
      return res.status(404).json({
        success: false,
        message: 'Profil belum tersedia.',
      });
    }

    const data = profile.toJSON();
    if (data.misi) {
      try {
        data.misi = JSON.parse(data.misi);
      } catch {
        // keep as string if not valid JSON
      }
    }

    return res.status(200).json({
      success: true,
      message: 'Berhasil mendapatkan profil.',
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

const upsertProfile = async (req, res) => {
  try {
    const { nama_instansi, visi, misi, sejarah, alamat, telepon, email, website } = req.body;

    let profile = await Profile.findOne();
    const updateData = {};

    if (nama_instansi !== undefined) updateData.nama_instansi = nama_instansi;
    if (visi !== undefined) updateData.visi = visi;
    if (sejarah !== undefined) updateData.sejarah = sejarah;
    if (alamat !== undefined) updateData.alamat = alamat;
    if (telepon !== undefined) updateData.telepon = telepon;
    if (email !== undefined) updateData.email = email;
    if (website !== undefined) updateData.website = website;

    if (misi !== undefined) {
      updateData.misi = Array.isArray(misi) ? JSON.stringify(misi) : misi;
    }

    // Handle uploaded files
    if (req.files) {
      if (req.files.logo) {
        if (profile && profile.logo) {
          const oldPath = path.join(process.env.UPLOAD_PATH || './uploads', profile.logo);
          if (fs.existsSync(oldPath)) fs.unlinkSync(oldPath);
        }
        updateData.logo = path.join('profile', req.files.logo[0].filename);
      }
      if (req.files.struktur_organisasi_gambar) {
        if (profile && profile.struktur_organisasi_gambar) {
          const oldPath = path.join(
            process.env.UPLOAD_PATH || './uploads',
            profile.struktur_organisasi_gambar
          );
          if (fs.existsSync(oldPath)) fs.unlinkSync(oldPath);
        }
        updateData.struktur_organisasi_gambar = path.join(
          'profile',
          req.files.struktur_organisasi_gambar[0].filename
        );
      }
    }

    if (!profile) {
      profile = await Profile.create(updateData);
    } else {
      await profile.update(updateData);
    }

    const data = profile.toJSON();
    if (data.misi) {
      try {
        data.misi = JSON.parse(data.misi);
      } catch {
        // keep as string
      }
    }

    return res.status(200).json({
      success: true,
      message: 'Profil berhasil disimpan.',
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

module.exports = { getProfile, upsertProfile };
