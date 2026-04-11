const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

// Tipe organisasi: sekretariat, aset, perbendaharaan, akuntansi, anggaran
const Organisasi = sequelize.define('Organisasi', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
  },
  nama: {
    type: DataTypes.STRING(255),
    allowNull: false,
  },
  tipe: {
    type: DataTypes.ENUM('sekretariat', 'aset', 'perbendaharaan', 'akuntansi', 'anggaran'),
    allowNull: false,
  },
  deskripsi: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
  urutan: {
    type: DataTypes.INTEGER,
    defaultValue: 0,
  },
}, {
  tableName: 'organisasis',
  timestamps: true,
});

module.exports = Organisasi;
