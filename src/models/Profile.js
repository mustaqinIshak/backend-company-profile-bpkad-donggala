const { DataTypes } = require('sequelize');
const sequelize = require('../config/database');

const Profile = sequelize.define('Profile', {
  id: {
    type: DataTypes.INTEGER,
    primaryKey: true,
    autoIncrement: true,
  },
  nama_instansi: {
    type: DataTypes.STRING(255),
    allowNull: false,
    defaultValue: 'BPKAD Kabupaten Donggala',
  },
  visi: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
  misi: {
    type: DataTypes.TEXT,
    allowNull: true,
    comment: 'JSON array of misi items',
  },
  sejarah: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
  alamat: {
    type: DataTypes.TEXT,
    allowNull: true,
  },
  telepon: {
    type: DataTypes.STRING(50),
    allowNull: true,
  },
  email: {
    type: DataTypes.STRING(100),
    allowNull: true,
  },
  website: {
    type: DataTypes.STRING(255),
    allowNull: true,
  },
  logo: {
    type: DataTypes.STRING(255),
    allowNull: true,
  },
  struktur_organisasi_gambar: {
    type: DataTypes.STRING(255),
    allowNull: true,
    comment: 'Path to organizational chart image',
  },
}, {
  tableName: 'profiles',
  timestamps: true,
});

module.exports = Profile;
