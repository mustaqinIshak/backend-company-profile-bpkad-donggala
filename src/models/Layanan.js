const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

// Tipe layanan: penganggaran, penatausahaan, pelaporan_pertanggungjawaban,
//               perencanaan_evaluasi, perjanjian_kinerja
const Layanan = sequelize.define('Layanan', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
  },
  tipe: {
    type: DataTypes.ENUM(
      'penganggaran',
      'penatausahaan',
      'pelaporan_pertanggungjawaban',
      'perencanaan_evaluasi',
      'perjanjian_kinerja'
    ),
    allowNull: false,
  },
  judul: {
    type: DataTypes.STRING(255),
    allowNull: false,
  },
  tahun_apbd: {
    type: DataTypes.STRING(10),
    allowNull: false,
    comment: 'Tahun APBD, e.g. 2024',
  },
  deskripsi: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
  file: {
    type: DataTypes.STRING(255),
    allowNull: false,
    comment: 'Path to uploaded file',
  },
  nama_file_asli: {
    type: DataTypes.STRING(255),
    allowNull: true,
    comment: 'Original uploaded file name',
  },
  aktif: {
    type: DataTypes.BOOLEAN,
    defaultValue: true,
  },
}, {
  tableName: 'layanans',
  timestamps: true,
});

module.exports = Layanan;
