const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Jabatan = sequelize.define('Jabatan', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
  },
  organisasi_id: {
    type: DataTypes.INTEGER,
    allowNull: false,
    references: {
      model: 'organisasis',
      key: 'id',
    },
  },
  nama_jabatan: {
    type: DataTypes.STRING(255),
    allowNull: false,
  },
  nama_pejabat: {
    type: DataTypes.STRING(255),
    allowNull: false,
  },
  nip: {
    type: DataTypes.STRING(50),
    allowNull: true,
  },
  foto: {
    type: DataTypes.STRING(255),
    allowNull: true,
  },
  tugas_fungsi: {
    type: DataTypes.TEXT,
    allowNull: true,
    comment: 'JSON array of tugas dan fungsi items',
  },
  urutan: {
    type: DataTypes.INTEGER,
    defaultValue: 0,
  },
}, {
  tableName: 'jabatans',
  timestamps: true,
});

module.exports = Jabatan;
