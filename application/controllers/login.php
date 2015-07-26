<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends MY_Controller
{
    function index()
    {
        if (is_login()) {
            redirect('welcome');
        }

        if ($this->form_validation->run('login') == TRUE) {
            $email    = $this->input->post('email', TRUE);
            $password = md5($this->input->post('password', TRUE));

            $get_login = $this->login_model->retrieve(null, $email, $password);

            if (empty($get_login)) {
                $this->session->set_flashdata('login', get_alert('warning', 'Maaf akun tidak ditemukan.'));
                redirect('login');
            } else {
                # cari user yang login
                if (!empty($get_login['pengajar_id'])) {
                    $user = $this->pengajar_model->retrieve($get_login['pengajar_id']);

                    $user_type = empty($get_login['is_admin']) ? 'pengajar' : 'admin';

                } elseif (!empty($get_login['siswa_id'])) {
                    $user = $this->siswa_model->retrieve($get_login['siswa_id']);

                    $user_type = 'siswa';
                }

                # cek jika user berstatus tidak aktif
                if ($user['status_id'] != 1) {
                    $this->session->set_flashdata('login', get_alert('warning', 'Maaf status anda tidak aktif.'));
                    redirect('login');
                }

                $data_session['login_' . APP_PREFIX][$user_type] = array(
                    'login' => $get_login,
                    'user'  => $user
                );

                $this->session->set_userdata($data_session);

                create_sess_kcfinder($get_login['id']);

                redirect('welcome');
            }
        }

        $this->twig->display('login.html');
    }

    function logout()
    {
        $_SESSION['E-LEARNING'] = array();
        $this->session->set_userdata('login_' . APP_PREFIX, null);
        $this->session->set_userdata('filter_pengajar', null);
        $this->session->set_userdata('filter_materi', null);
        $this->session->set_userdata('filter_tugas', null);
        $this->session->set_userdata('filter_siswa', null);
        $this->session->set_userdata('mengerjakan_tugas', null);

        redirect('login/index');
    }

    function pp()
    {
        must_login();

        if (is_pengajar()) {
            # panggil colorbox
            $html_js = load_comp_js(array(
                base_url('assets/comp/colorbox/jquery.colorbox-min.js'),
                base_url('assets/comp/colorbox/act-pengajar.js')
            ));
            $data['comp_js']  = $html_js;
            $data['comp_css'] = load_comp_css(array(base_url('assets/comp/colorbox/colorbox.css')));

            $data['pengajar']       = $this->pengajar_model->retrieve(get_sess_data('user', 'id'));
            $data['pengajar_login'] = $this->login_model->retrieve(get_sess_data('login', 'id'));
            $data['status_id']      = get_sess_data('user', 'status_id');

            $this->twig->display('pp-pengajar.html', $data);
        }

        if (is_siswa()) {
            $retrieve_siswa     = $this->siswa_model->retrieve(get_sess_data('user', 'id'));
            $retrieve_login     = $this->login_model->retrieve(get_sess_data('login', 'id'));
            $retrieve_all_kelas = $this->kelas_model->retrieve_all_siswa(10, 1, array('siswa_id' => $retrieve_siswa['id']));

            $data['siswa']       = $retrieve_siswa;
            $data['siswa_login'] = $retrieve_login;
            $data['siswa_kelas'] = $retrieve_all_kelas;
            $data['status_id']   = get_sess_data('user', 'status_id');

            # panggil colorbox
            $html_js = load_comp_js(array(
                base_url('assets/comp/colorbox/jquery.colorbox-min.js'),
                base_url('assets/comp/colorbox/act-siswa.js')
            ));
            $data['comp_js']  = $html_js;
            $data['comp_css'] = load_comp_css(array(base_url('assets/comp/colorbox/colorbox.css')));

            $this->twig->display('pp-siswa.html', $data);
        }
    }
}
