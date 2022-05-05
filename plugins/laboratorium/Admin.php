<?php
namespace Plugins\Laboratorium;

use Systems\AdminModule;
use Plugins\Icd\DB_ICD;
use Systems\Lib\QRCode;

class Admin extends AdminModule
{

    public function navigation()
    {
        return [
            'Kelola'   => 'manage',
        ];
    }

    public function anyManage()
    {
        $tgl_kunjungan = date('Y-m-d');
        $tgl_kunjungan_akhir = date('Y-m-d');
        $status_periksa = '';
        $status_bayar = '';

        if(isset($_POST['periode_rawat_jalan'])) {
          $tgl_kunjungan = $_POST['periode_rawat_jalan'];
        }
        if(isset($_POST['periode_rawat_jalan_akhir'])) {
          $tgl_kunjungan_akhir = $_POST['periode_rawat_jalan_akhir'];
        }
        if(isset($_POST['status_periksa'])) {
          $status_periksa = $_POST['status_periksa'];
        }
        if(isset($_POST['status_bayar'])) {
          $status_bayar = $_POST['status_bayar'];
        }
        $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
        $this->_Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa, $status_bayar);
        return $this->draw('manage.html', ['rawat_jalan' => $this->assign, 'cek_vclaim' => $cek_vclaim]);
    }

    public function anyDisplay()
    {
        $tgl_kunjungan = date('Y-m-d');
        $tgl_kunjungan_akhir = date('Y-m-d');
        $status_periksa = '';

        if(isset($_POST['periode_rawat_jalan'])) {
          $tgl_kunjungan = $_POST['periode_rawat_jalan'];
        }
        if(isset($_POST['periode_rawat_jalan_akhir'])) {
          $tgl_kunjungan_akhir = $_POST['periode_rawat_jalan_akhir'];
        }
        if(isset($_POST['status_periksa'])) {
          $status_periksa = $_POST['status_periksa'];
        }
        $cek_vclaim = $this->db('mlite_modules')->where('dir', 'vclaim')->oneArray();
        $this->_Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa);
        echo $this->draw('display.html', ['rawat_jalan' => $this->assign, 'cek_vclaim' => $cek_vclaim]);
        exit();
    }

    public function _Display($tgl_kunjungan, $tgl_kunjungan_akhir, $status_periksa='')
    {
        $this->_addHeaderFiles();

        $this->assign['poliklinik']     = $this->core->mysql('poliklinik')->where('status', '1')->toArray();
        $this->assign['dokter']         = $this->core->mysql('dokter')->where('status', '1')->toArray();
        $this->assign['penjab']       = $this->core->mysql('penjab')->toArray();
        $this->assign['no_rawat'] = '';
        $this->assign['no_reg']     = '';
        $this->assign['tgl_registrasi']= date('Y-m-d');
        $this->assign['jam_reg']= date('H:i:s');

        $sql = "SELECT reg_periksa.*,
            pasien.*,
            dokter.*,
            poliklinik.*,
            penjab.*
          FROM reg_periksa, pasien, dokter, poliklinik, penjab
          WHERE reg_periksa.no_rkm_medis = pasien.no_rkm_medis
          AND reg_periksa.tgl_registrasi BETWEEN '$tgl_kunjungan' AND '$tgl_kunjungan_akhir'
          AND reg_periksa.kd_dokter = dokter.kd_dokter
          AND reg_periksa.kd_poli = poliklinik.kd_poli
          AND reg_periksa.kd_pj = penjab.kd_pj";

        if($status_periksa == 'belum') {
          $sql .= " AND reg_periksa.stts = 'Belum'";
        }
        if($status_periksa == 'selesai') {
          $sql .= " AND reg_periksa.stts = 'Sudah'";
        }
        if($status_periksa == 'lunas') {
          $sql .= " AND reg_periksa.status_bayar = 'Sudah Bayar'";
        }

        $stmt = $this->core->mysql()->pdo()->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $this->assign['list'] = [];
        foreach ($rows as $row) {
          $this->assign['list'][] = $row;
        }

        if (isset($_POST['no_rawat'])){
          $this->assign['reg_periksa'] = $this->core->mysql('reg_periksa')
            ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
            ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
            ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
            ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
            ->where('no_rawat', $_POST['no_rawat'])
            ->oneArray();
        } else {
          $this->assign['reg_periksa'] = [
            'no_rkm_medis' => '',
            'nm_pasien' => '',
            'no_reg' => '',
            'no_rawat' => '',
            'tgl_registrasi' => '',
            'jam_reg' => '',
            'kd_dokter' => '',
            'no_rm' => '',
            'kd_poli' => '',
            'p_jawab' => '',
            'almt_pj' => '',
            'hubunganpj' => '',
            'biaya_reg' => '',
            'stts' => '',
            'stts_daftar' => '',
            'status_lanjut' => '',
            'kd_pj' => '',
            'umurdaftar' => '',
            'sttsumur' => '',
            'status_bayar' => '',
            'status_poli' => '',
            'nm_pasien' => '',
            'tgl_lahir' => '',
            'jk' => '',
            'alamat' => '',
            'no_tlp' => '',
            'pekerjaan' => ''
          ];
        }
    }

    public function anyForm()
    {

      $this->assign['poliklinik'] = $this->core->mysql('poliklinik')->where('status', '1')->toArray();
      $this->assign['dokter'] = $this->core->mysql('dokter')->where('status', '1')->toArray();
      $this->assign['penjab'] = $this->core->mysql('penjab')->toArray();
      $this->assign['no_rawat'] = '';
      $this->assign['no_reg']     = '';
      $this->assign['tgl_registrasi']= date('Y-m-d');
      $this->assign['jam_reg']= date('H:i:s');
      if (isset($_POST['no_rawat'])){
        $this->assign['reg_periksa'] = $this->core->mysql('reg_periksa')
          ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
          ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
          ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
          ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
          ->where('no_rawat', $_POST['no_rawat'])
          ->oneArray();
        echo $this->draw('form.html', [
          'rawat_jalan' => $this->assign
        ]);
      } else {
        $this->assign['reg_periksa'] = [
          'no_rkm_medis' => '',
          'nm_pasien' => '',
          'no_reg' => '',
          'no_rawat' => '',
          'tgl_registrasi' => '',
          'jam_reg' => '',
          'kd_dokter' => '',
          'no_rm' => '',
          'kd_poli' => '',
          'p_jawab' => '',
          'almt_pj' => '',
          'hubunganpj' => '',
          'biaya_reg' => '',
          'stts' => '',
          'stts_daftar' => '',
          'status_lanjut' => '',
          'kd_pj' => '',
          'umurdaftar' => '',
          'sttsumur' => '',
          'status_bayar' => '',
          'status_poli' => '',
          'nm_pasien' => '',
          'tgl_lahir' => '',
          'jk' => '',
          'alamat' => '',
          'no_tlp' => '',
          'pekerjaan' => ''
        ];
        echo $this->draw('form.html', [
          'rawat_jalan' => $this->assign
        ]);
      }
      exit();
    }

    public function anyStatusDaftar()
    {
      if(isset($_POST['no_rkm_medis'])) {
        $rawat = $this->core->mysql('reg_periksa')
          ->where('no_rkm_medis', $_POST['no_rkm_medis'])
          ->where('status_bayar', 'Belum Bayar')
          ->limit(1)
          ->oneArray();
          if($rawat) {
            $stts_daftar ="Transaki tanggal ".date('Y-m-d', strtotime($rawat['tgl_registrasi']))." belum diselesaikan" ;
            $bg_status = 'text-danger';
          } else {
            $result = $this->core->mysql('reg_periksa')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();
            if($result >= 1) {
              $stts_daftar = 'Lama';
              $bg_status = 'text-info';
            } else {
              $stts_daftar = 'Baru';
              $bg_status = 'text-success';
            }
          }
        echo $this->draw('stts.daftar.html', ['stts_daftar' => $stts_daftar, 'bg_status' =>$bg_status]);
      } else {
        $rawat = $this->core->mysql('reg_periksa')
          ->where('no_rawat', $_POST['no_rawat'])
          ->oneArray();
        echo $this->draw('stts.daftar.html', ['stts_daftar' => $rawat['stts_daftar']]);
      }
      exit();
    }

    public function postSave()
    {
      if (!$this->core->mysql('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->oneArray()) {

        $_POST['status_lanjut'] = 'Ralan';
        $_POST['stts'] = 'Belum';
        $_POST['status_bayar'] = 'Belum Bayar';
        $_POST['p_jawab'] = '-';
        $_POST['almt_pj'] = '-';
        $_POST['hubunganpj'] = '-';

        $poliklinik = $this->core->mysql('poliklinik')->where('kd_poli', $this->settings('settings', 'laboratorium'))->oneArray();

        $_POST['biaya_reg'] = $poliklinik['registrasi'];

        $pasien = $this->core->mysql('pasien')->where('no_rkm_medis', $_POST['no_rkm_medis'])->oneArray();

      	$birthDate = new \DateTime($pasien['tgl_lahir']);
      	$today = new \DateTime("today");
      	$umur_daftar = "0";
        $status_umur = 'Hr';
        if ($birthDate < $today) {
        	$y = $today->diff($birthDate)->y;
        	$m = $today->diff($birthDate)->m;
        	$d = $today->diff($birthDate)->d;
          $umur_daftar = $d;
          $status_umur = "Hr";
          if($y !='0'){
            $umur_daftar = $y;
            $status_umur = "Th";
          }
          if($y =='0' && $m !='0'){
            $umur_daftar = $m;
            $status_umur = "Bl";
          }
        }

        $_POST['umurdaftar'] = $umur_daftar;
        $_POST['sttsumur'] = $status_umur;
        $_POST['status_poli'] = 'Lama';
        $_POST['kd_poli'] = $this->settings('settings', 'laboratorium');

        $query = $this->core->mysql('reg_periksa')->save($_POST);
      } else {
        $query = $this->core->mysql('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->valu([
          'kd_dokter' => $_POST['kd_dokter'],
          'kd_pj' => $_POST['kd_pj']
        ]);
      }
      exit();
    }

    public function anyPasien()
    {
      if(isset($_POST['cari'])) {
        $pasien = $this->core->mysql('pasien')
          ->like('no_rkm_medis', '%'.$_POST['cari'].'%')
          ->orLike('nm_pasien', '%'.$_POST['cari'].'%')
          ->asc('no_rkm_medis')
          ->limit(5)
          ->toArray();
      }
      echo $this->draw('pasien.html', ['pasien' => $pasien]);
      exit();
    }

    public function getAntrian()
    {
      $settings = $this->settings('settings');
      $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
      $rawat_jalan = $this->core->mysql('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->join('dokter', 'dokter.kd_dokter=reg_periksa.kd_dokter')
        ->join('penjab', 'penjab.kd_pj=reg_periksa.kd_pj')
        ->where('no_rawat', $_GET['no_rawat'])
        ->oneArray();
      echo $this->draw('antrian.html', ['rawat_jalan' => $rawat_jalan]);
      exit();
    }

    public function getCetakHasil()
    {
      $settings = $this->settings('settings');
      $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
      $pj_lab = $this->core->mysql('dokter')->where('kd_dokter', $this->settings->get('settings.pj_laboratorium'))->oneArray();
      $qr = QRCode::getMinimumQRCode($pj_lab['nm_dokter'], QR_ERROR_CORRECT_LEVEL_L);
      $im = $qr->createImage(4, 4);
      imagepng($im, BASE_DIR .'/admin/tmp/qrcode.png');
      imagedestroy($im);
      $qrCode = "../../admin/tmp/qrcode.png";

      $pasien = $this->core->mysql('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->where('no_rawat', $_GET['no_rawat'])
        ->oneArray();
      $dokter_perujuk = $this->core->mysql('periksa_lab')
        ->join('pegawai', 'pegawai.nik=periksa_lab.dokter_perujuk')
        ->where('no_rawat', $_GET['no_rawat'])
        ->group('no_rawat')
        ->oneArray();

      $rows_periksa_lab = $this->core->mysql('periksa_lab')
      ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw')
      ->where('no_rawat', $_GET['no_rawat'])
      ->toArray();

      $periksa_lab = [];
      $jumlah_total_lab = 0;
      $no_lab = 1;
      foreach ($rows_periksa_lab as $row) {
        $jumlah_total_lab += $row['biaya'];
        $row['nomor'] = $no_lab++;
        $row['detail_periksa_lab'] = $this->core->mysql('detail_periksa_lab')
          ->join('template_laboratorium', 'template_laboratorium.id_template=detail_periksa_lab.id_template')
          ->where('detail_periksa_lab.no_rawat', $_GET['no_rawat'])
          ->where('detail_periksa_lab.kd_jenis_prw', $row['kd_jenis_prw'])
          ->toArray();
        $periksa_lab[] = $row;
      }

      echo $this->draw('cetakhasil.html', [
        'periksa_lab' => $periksa_lab,
        'jumlah_total_lab' => $jumlah_total_lab,
        'qrCode' => $qrCode,
        'pj_lab' => $pj_lab['nm_dokter'],
        'dokter_perujuk' => $dokter_perujuk['nama'],
        'pasien' => $pasien,
        'no_rawat' => $_GET['no_rawat']
      ]);
      exit();
    }

    public function getCetakPermintaan()
    {
      $settings = $this->settings('settings');
      $this->tpl->set('settings', $this->tpl->noParse_array(htmlspecialchars_array($settings)));
      $pj_lab = $this->core->mysql('dokter')->where('kd_dokter', $this->settings->get('settings.pj_laboratorium'))->oneArray();

      $qr = QRCode::getMinimumQRCode($pj_lab['nm_dokter'], QR_ERROR_CORRECT_LEVEL_L);
      $im = $qr->createImage(4, 4);
      imagepng($im, '../../../tmp/qrcode.png');
      imagedestroy($im);
      $qrCode = "../../../tmp/qrcode.png";

      $pasien = $this->core->mysql('reg_periksa')
        ->join('pasien', 'pasien.no_rkm_medis=reg_periksa.no_rkm_medis')
        ->join('poliklinik', 'poliklinik.kd_poli=reg_periksa.kd_poli')
        ->where('no_rawat', $_GET['no_rawat'])
        ->oneArray();
      $dokter_perujuk = $this->core->mysql('permintaan_lab')
        ->join('pegawai', 'pegawai.nik=permintaan_lab.dokter_perujuk')
        ->where('no_rawat', $_GET['no_rawat'])
        ->group('no_rawat')
        ->oneArray();

      $rows_permintaan_lab = $this->core->mysql('permintaan_lab')
        ->join('dokter', 'dokter.kd_dokter=permintaan_lab.dokter_perujuk')
        ->where('no_rawat', $_GET['no_rawat'])
        ->where('permintaan_lab.status', 'ralan')
        ->toArray();
      $permintaan_laboratorium = [];
      foreach ($rows_permintaan_lab as $row) {
        $rows2 = $this->core->mysql('permintaan_pemeriksaan_lab')
          ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=permintaan_pemeriksaan_lab.kd_jenis_prw')
          ->where('permintaan_pemeriksaan_lab.noorder', $row['noorder'])
          ->toArray();
          foreach ($rows2 as $row2) {
            $row2['noorder'] = $row2['noorder'];
            $row2['kd_jenis_prw'] = $row2['kd_jenis_prw'];
            $row2['stts_bayar'] = $row2['stts_bayar'];
            $row2['nm_perawatan'] = $row2['nm_perawatan'];
            $row2['kd_pj'] = $row2['kd_pj'];
            $row2['status'] = $row2['status'];
            $row2['kelas'] = $row2['kelas'];
            $row2['kategori'] = $row2['kategori'];
            $row2['template_laboratorium'] = $this->core->mysql('template_laboratorium')->where('kd_jenis_prw', $row2['kd_jenis_prw'])->toArray();
            $row['permintaan_pemeriksaan_lab'][] = $row2;
          }
        $permintaan_laboratorium[] = $row;
      }

      echo $this->draw('cetakpermintaan.html', [
        'permintaan_laboratorium' => $permintaan_laboratorium,
        'qrCode' => $qrCode,
        'pj_lab' => $pj_lab['nm_dokter'],
        'dokter_perujuk' => $dokter_perujuk['nama'],
        'pasien' => $pasien,
        'no_rawat' => $_GET['no_rawat']
      ]);
      exit();
    }

    public function postHapus()
    {
      $this->core->mysql('reg_periksa')->where('no_rawat', $_POST['no_rawat'])->delete();
      exit();
    }

    public function postSaveDetail()
    {

      if($_POST['kat'] == 'laboratorium') {
        $jns_perawatan = $this->core->mysql('jns_perawatan_lab')->where('kd_jenis_prw', $_POST['kd_jenis_prw'])->oneArray();
        $periksa_lab = $this->core->mysql('periksa_lab')
          ->save([
            'no_rawat' => $_POST['no_rawat'],
            'nip' => $this->core->getUserInfo('username', null, true),
            'kd_jenis_prw' => $_POST['kd_jenis_prw'],
            'tgl_periksa' => $_POST['tgl_perawatan'],
            'jam' => $_POST['jam_rawat'],
            'dokter_perujuk' => $_POST['kode_provider'],
            'bagian_rs' => $jns_perawatan['bagian_rs'],
            'bhp' => $jns_perawatan['bhp'],
            'tarif_perujuk' => $jns_perawatan['tarif_perujuk'],
            'tarif_tindakan_dokter' => $jns_perawatan['tarif_tindakan_dokter'],
            'tarif_tindakan_petugas' => $jns_perawatan['tarif_tindakan_petugas'],
            'kso' => $jns_perawatan['kso'],
            'menejemen' => $jns_perawatan['menejemen'],
            'biaya' => $jns_perawatan['total_byr'],
            'kd_dokter' => $this->settings->get('settings.pj_laboratorium'),
            'status' => 'Ralan'
          ]);
        if($periksa_lab) {
          $template_laboratorium = $this->core->mysql('template_laboratorium')->where('kd_jenis_prw', $_POST['kd_jenis_prw'])->toArray();
          foreach ($template_laboratorium as $row) {
            $this->core->mysql('detail_periksa_lab')
              ->save([
                'no_rawat' => $_POST['no_rawat'],
                'kd_jenis_prw' => $_POST['kd_jenis_prw'],
                'tgl_periksa' => $_POST['tgl_perawatan'],
                'jam' => $_POST['jam_rawat'],
                'id_template' => $row['id_template'],
                'nilai' => '',
                'nilai_rujukan' => $row['nilai_rujukan_ld'],
                'keterangan' => '',
                'bagian_rs' => $row['bagian_rs'],
                'bhp' => $row['bhp'],
                'bagian_perujuk' => $row['bagian_perujuk'],
                'bagian_dokter' => $row['bagian_dokter'],
                'bagian_laborat' => $row['bagian_laborat'],
                'kso' => $row['kso'],
                'menejemen' => $row['menejemen'],
                'biaya_item' => $row['biaya_item']
              ]);
          }
        }
      }

      exit();
    }

    public function anySaveNilai()
    {
      $this->core->mysql('detail_periksa_lab')
        ->where('no_rawat', $_REQUEST['no_rawat'])
        ->where('tgl_periksa', $_REQUEST['tgl_periksa'])
        ->where('jam', $_REQUEST['jam'])
        ->where('id_template', $_REQUEST['id_template'])
        ->save([
          'nilai' => $_REQUEST['value']
        ]);

      exit();
    }

    public function anySaveKeterangan()
    {
      $this->core->mysql('detail_periksa_lab')
        ->where('no_rawat', $_REQUEST['no_rawat'])
        ->where('tgl_periksa', $_REQUEST['tgl_periksa'])
        ->where('jam', $_REQUEST['jam'])
        ->where('id_template', $_REQUEST['id_template'])
        ->save([
          'keterangan' => $_REQUEST['value']
        ]);
      exit();
    }

    public function postHapusLaboratorium()
    {
      $periksa_lab = $this->core->mysql('periksa_lab')
      ->where('no_rawat', $_POST['no_rawat'])
      ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
      ->where('tgl_periksa', $_POST['tgl_perawatan'])
      ->where('jam', $_POST['jam_rawat'])
      ->where('status', 'Ralan')
      ->delete();
      if($periksa_lab) {
        $this->core->mysql('detail_periksa_lab')
        ->where('no_rawat', $_POST['no_rawat'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('tgl_periksa', $_POST['tgl_perawatan'])
        ->where('jam', $_POST['jam_rawat'])
        ->delete();
      }
      exit();
    }

    public function anyRincian()
    {

      $check_db = $this->core->mysql()->pdo()->query("SHOW TABLES LIKE 'permintaan_lab'");
      $check_db->execute();
      $check_db = $check_db->fetch();

      $laboratorium = [];
      if($check_db) {
        $rows = $this->core->mysql('permintaan_lab')
          ->join('dokter', 'dokter.kd_dokter=permintaan_lab.dokter_perujuk')
          ->where('no_rawat', $_POST['no_rawat'])
          ->where('permintaan_lab.status', 'ralan')
          ->toArray();
        $laboratorium = [];
        foreach ($rows as $row) {
          $rows2 = $this->core->mysql('permintaan_pemeriksaan_lab')
            ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=permintaan_pemeriksaan_lab.kd_jenis_prw')
            //->join('permintaan_detail_permintaan_lab', 'permintaan_detail_permintaan_lab.noorder=permintaan_pemeriksaan_lab.noorder')
            ->where('permintaan_pemeriksaan_lab.noorder', $row['noorder'])
            ->toArray();
            $row['permintaan_pemeriksaan_lab'] = [];
            foreach ($rows2 as $row2) {
              $row2['noorder'] = $row2['noorder'];
              $row2['kd_jenis_prw'] = $row2['kd_jenis_prw'];
              $row2['stts_bayar'] = $row2['stts_bayar'];
              $row2['nm_perawatan'] = $row2['nm_perawatan'];
              $row2['kd_pj'] = $row2['kd_pj'];
              $row2['status'] = $row2['status'];
              $row2['kelas'] = $row2['kelas'];
              $row2['kategori'] = $row2['kategori'];
              $rows3 = $this->core->mysql('permintaan_detail_permintaan_lab')->where('noorder', $row2['noorder'])->where('kd_jenis_prw', $row2['kd_jenis_prw'])->toArray();
              $row2['permintaan_detail_permintaan_lab'] = [];
              foreach ($rows3 as $row3) {
                $row3['template_laboratorium'] = $this->core->mysql('template_laboratorium')->where('kd_jenis_prw', $row3['kd_jenis_prw'])->where('id_template', $row3['id_template'])->oneArray();
                $row2['permintaan_detail_permintaan_lab'][] = $row3;
              }
              $row['permintaan_pemeriksaan_lab'][] = $row2;
            }
          $laboratorium[] = $row;
        }
      }

      $rows_periksa_lab = $this->core->mysql('periksa_lab')
      ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw=periksa_lab.kd_jenis_prw')
      ->where('no_rawat', $_POST['no_rawat'])
      ->toArray();

      $periksa_lab = [];
      $jumlah_total_lab = 0;
      $no_lab = 1;
      foreach ($rows_periksa_lab as $row) {
        $jumlah_total_lab += $row['biaya'];
        $row['nomor'] = $no_lab++;
        $row['detail_periksa_lab'] = $this->core->mysql('detail_periksa_lab')
          ->join('template_laboratorium', 'template_laboratorium.id_template=detail_periksa_lab.id_template')
          ->where('detail_periksa_lab.no_rawat', $_POST['no_rawat'])
          ->where('detail_periksa_lab.kd_jenis_prw', $row['kd_jenis_prw'])
          ->toArray();
        $periksa_lab[] = $row;
      }

      echo $this->draw('rincian.html', ['periksa_lab' => $periksa_lab, 'jumlah_total_lab' => $jumlah_total_lab, 'no_rawat' => $_POST['no_rawat'], 'laboratorium' => $laboratorium, 'check_permintaan_lab' => $check_db]);
      exit();
    }

    public function postHapusPermintaanLaboratorium()
    {
      $this->core->mysql('permintaan_lab')
      ->where('no_rawat', $_POST['no_rawat'])
      ->where('noorder', $_POST['noorder'])
      ->where('tgl_permintaan', $_POST['tgl_permintaan'])
      ->where('jam_permintaan', $_POST['jam_permintaan'])
      ->where('status', 'Ralan')
      ->delete();
      exit();
    }

    public function getDetailPermintaan($noorder, $kd_jenis_prw)
    {
      $rows = $this->core->mysql('permintaan_detail_permintaan_lab')->where('noorder', $noorder)->where('kd_jenis_prw', $kd_jenis_prw)->toArray();
      $detail_permintaan_lab = [];
      foreach ($rows as $row) {
        $row['template_laboratorium'] = $this->core->mysql('template_laboratorium')->where('kd_jenis_prw', $row['kd_jenis_prw'])->where('id_template', $row['id_template'])->oneArray();
        $detail_permintaan_lab[] = $row;
      }
      $this->tpl->set('detail', $detail_permintaan_lab);
      echo $this->tpl->draw(MODULES.'/laboratorium/view/admin/details.html', true);
      exit();
    }

    public function postValidasiPermintaanLab()
    {
      $permintaan_lab = $this->core->mysql('permintaan_lab')->where('no_rawat', $_POST['no_rawat'])->where('noorder', $_POST['noorder'])->oneArray();
      $permintaan_pemeriksaan_lab = $this->core->mysql('permintaan_pemeriksaan_lab')->where('noorder', $_POST['noorder'])->oneArray();
      $jns_perawatan = $this->core->mysql('jns_perawatan_lab')->where('kd_jenis_prw', $permintaan_pemeriksaan_lab['kd_jenis_prw'])->oneArray();
      $periksa_lab = $this->core->mysql('periksa_lab')
        ->save([
          'no_rawat' => $_POST['no_rawat'],
          'nip' => $this->core->getUserInfo('username', null, true),
          'kd_jenis_prw' => $permintaan_pemeriksaan_lab['kd_jenis_prw'],
          'tgl_periksa' => $_POST['tgl_permintaan'],
          'jam' => $_POST['jam_permintaan'],
          'dokter_perujuk' => $permintaan_lab['dokter_perujuk'],
          'bagian_rs' => $jns_perawatan['bagian_rs'],
          'bhp' => $jns_perawatan['bhp'],
          'tarif_perujuk' => $jns_perawatan['tarif_perujuk'],
          'tarif_tindakan_dokter' => $jns_perawatan['tarif_tindakan_dokter'],
          'tarif_tindakan_petugas' => $jns_perawatan['tarif_tindakan_petugas'],
          'kso' => $jns_perawatan['kso'],
          'menejemen' => $jns_perawatan['menejemen'],
          'biaya' => $jns_perawatan['total_byr'],
          'kd_dokter' => $this->settings->get('settings.pj_laboratorium'),
          'status' => 'Ralan'
        ]);
      if($periksa_lab) {
        $permintaan_detail_permintaan_lab = $this->core->mysql('permintaan_detail_permintaan_lab')->where('noorder', $_POST['noorder'])->toArray();
        foreach ($permintaan_detail_permintaan_lab as $row) {
          $template_laboratorium = $this->core->mysql('template_laboratorium')->where('kd_jenis_prw', $row['kd_jenis_prw'])->where('id_template', $row['id_template'])->oneArray();
          $this->core->mysql('detail_periksa_lab')
            ->save([
              'no_rawat' => $_POST['no_rawat'],
              'kd_jenis_prw' => $row['kd_jenis_prw'],
              'tgl_periksa' => $_POST['tgl_permintaan'],
              'jam' => $_POST['jam_permintaan'],
              'id_template' => $row['id_template'],
              'nilai' => '',
              'nilai_rujukan' => $template_laboratorium['nilai_rujukan_ld'],
              'keterangan' => '',
              'bagian_rs' => $template_laboratorium['bagian_rs'],
              'bhp' => $template_laboratorium['bhp'],
              'bagian_perujuk' => $template_laboratorium['bagian_perujuk'],
              'bagian_dokter' => $template_laboratorium['bagian_dokter'],
              'bagian_laborat' => $template_laboratorium['bagian_laborat'],
              'kso' => $template_laboratorium['kso'],
              'menejemen' => $template_laboratorium['menejemen'],
              'biaya_item' => $template_laboratorium['biaya_item']
            ]);
        }
      }
      var_dump($permintaan_lab);
      var_dump($permintaan_pemeriksaan_lab);
      exit();
    }

    public function postHapusDetailPermintaan()
    {
      $this->core->mysql('permintaan_detail_permintaan_lab')
        ->where('noorder', $_POST['noorder'])
        ->where('kd_jenis_prw', $_POST['kd_jenis_prw'])
        ->where('id_template', $_POST['id_template'])
        ->delete();
      exit();
    }

    public function anyLayananLab()
    {
      $layanan = $this->core->mysql('jns_perawatan_lab')
        ->where('status', '1')
        ->like('nm_perawatan', '%'.$_POST['layanan'].'%')
        ->limit(10)
        ->toArray();
      echo $this->draw('layanan.html', ['layanan' => $layanan]);
      exit();
    }

    public function postProviderList()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->core->mysql('dokter')->like('nm_dokter', $key)->where('status', '1')->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.$row["kd_dokter"].': '.$row["nm_dokter"].'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function postProviderList2()
    {

      if(isset($_POST["query"])){
        $output = '';
        $key = "%".$_POST["query"]."%";
        $rows = $this->core->mysql('petugas')->like('nama', $key)->limit(10)->toArray();
        $output = '';
        if(count($rows)){
          foreach ($rows as $row) {
            $output .= '<li class="list-group-item link-class">'.$row["nip"].': '.$row["nama"].'</li>';
          }
        }
        echo $output;
      }

      exit();

    }

    public function postMaxid()
    {
      $max_id = $this->core->mysql('reg_periksa')->select(['no_rawat' => 'ifnull(MAX(CONVERT(RIGHT(no_rawat,6),signed)),0)'])->where('tgl_registrasi', date('Y-m-d'))->oneArray();
      if(empty($max_id['no_rawat'])) {
        $max_id['no_rawat'] = '000000';
      }
      $_next_no_rawat = sprintf('%06s', ($max_id['no_rawat'] + 1));
      $next_no_rawat = date('Y/m/d').'/'.$_next_no_rawat;
      echo $next_no_rawat;
      exit();
    }

    public function postMaxAntrian()
    {
      $max_id = $this->core->mysql('reg_periksa')->select(['no_reg' => 'ifnull(MAX(CONVERT(RIGHT(no_reg,3),signed)),0)'])->where('kd_poli', $this->settings('settings', 'laboratorium'))->where('tgl_registrasi', date('Y-m-d'))->desc('no_reg')->limit(1)->oneArray();
      if(empty($max_id['no_reg'])) {
        $max_id['no_reg'] = '000';
      }
      $_next_no_reg = sprintf('%03s', ($max_id['no_reg'] + 1));
      echo $_next_no_reg;
      exit();
    }

    public function convertNorawat($text)
    {
        setlocale(LC_ALL, 'en_EN');
        $text = str_replace('/', '', trim($text));
        return $text;
    }

    public function getJavascript()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/laboratorium/js/admin/laboratorium.js');
        exit();
    }

    private function _addHeaderFiles()
    {
        $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
        $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'));
        $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'));
        $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
        $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
        $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));
        $this->core->addJS(url([ADMIN, 'laboratorium', 'javascript']), 'footer');
    }

    protected function data_icd($table)
    {
        return new DB_ICD($table);
    }

}
