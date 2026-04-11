const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Berita = sequelize.define('Berita', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
  },
  judul: {
    type: DataTypes.STRING(255),
    allowNull: false,
  },
  slug: {
    type: DataTypes.STRING(300),
    allowNull: false,
    unique: true,
  },
  isi: {
    type: DataTypes.TEXT,
    allowNull: false,
  },
  gambar: {
    type: DataTypes.STRING(255),
    allowNull: true,
  },
  kategori: {
    type: DataTypes.STRING(100),
    allowNull: true,
    defaultValue: 'Kegiatan',
  },
  tanggal_publish: {
    type: DataTypes.DATEONLY,
    allowNull: true,
  },
  aktif: {
    type: DataTypes.BOOLEAN,
    defaultValue: true,
  },
}, {
  tableName: 'beritas',
  timestamps: true,
});

module.exports = Berita;
