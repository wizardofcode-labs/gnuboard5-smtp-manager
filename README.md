# SMTP Manager for Gnuboard5 / YoungCart

> 그누보드5 / 영카트5 용 SMTP 메일 발송 플러그인

**제작사:** K3SOFT
**회사명:** WizardOfCode
**버전:** v1.1.0
**최소 요구 버전:** 그누보드5 5.5.x 이상 / 영카트5 호환

---

## 소개

그누보드5의 코어 파일을 **단 한 줄도 수정하지 않고** extend 방식만으로 SMTP 메일 발송 기능을 추가하는 플러그인입니다.

기본 그누보드5는 PHP의 `mail()` 함수를 사용합니다. 서버 메일 설정이 올바르지 않으면 메일이 스팸으로 분류되거나 발송되지 않는 경우가 많습니다. 이 플러그인을 사용하면 Gmail, Naver, Daum, SendGrid 등 외부 SMTP 서버를 통해 안정적으로 메일을 발송할 수 있습니다.

### 주요 기능

- **SMTP 메일 발송**: 외부 SMTP 서버(Gmail, Naver 등)를 통한 안정적인 메일 발송
- **SSL / TLS / None** 보안 방식 선택 지원
- **발신자 이름 / 이메일** 별도 설정
- **테스트 메일 발송**: 설정 저장 후 즉시 발송 테스트 가능
- **메일 발송 로그**: 성공/실패 이력 관리자 페이지에서 확인
- **메일 본문 팝업 뷰어**: 로그에서 제목 클릭 시 발송된 메일 원본 내용 확인
- **코어 무수정**: extend 방식으로 동작하여 그누보드 업그레이드 시에도 안전

---

## 파일 구성

```
smtp_manager/
├── README.md                                   ← 이 파일
├── extend/
│   └── smtp.extend.php                         ← 그누보드 자동 로드 훅 파일
├── plugin/
│   └── smtp_manager/
│       ├── install.php                         ← 설치 페이지 (DB 스키마 생성)
│       ├── admin/
│       │   └── smtp_log.php                    ← 메일 발송 로그 관리 페이지
│       │   └── smtp_log_delete.php             ← 메일 발송 로그 삭제 페이지
│       └── lib/
│           └── smtp.lib.php                    ← 핵심 라이브러리
└── adm/
    ├── smtp_manager_config.php                 ← SMTP 설정 관리 페이지
    ├── smtp_manager_config_update.php          ← 설정 저장 처리
    ├── smtp_manager_test_mail.php              ← 테스트 메일 발송 처리
    └── smtp_manager_log_view.php               ← 메일 본문 AJAX 조회
```

---

## 설치 방법

### 1단계 — 파일 복사

압축 파일 내 폴더 구조를 그대로 그누보드 루트 디렉토리에 복사합니다.

| 패키지 경로 | 복사 대상 경로 |
|---|---|
| `extend/smtp.extend.php` | `[그누보드루트]/extend/smtp.extend.php` |
| `plugin/smtp_manager/` | `[그누보드루트]/plugin/smtp_manager/` |
| `adm/smtp_manager_*.php` | `[그누보드루트]/adm/smtp_manager_*.php` |

> **주의:** `extend/` 폴더에 이미 다른 `.extend.php` 파일이 있는 경우 덮어쓰지 마세요. `smtp.extend.php` 파일만 추가하면 됩니다.

### 2단계 — 설치 페이지 실행

브라우저에서 아래 URL로 접속하여 DB 테이블 및 컬럼을 생성합니다.

```
https://[사이트주소]/plugin/smtp_manager/install.php
```

설치가 완료되면 아래 메시지가 표시됩니다.
- `smtp_use 필드 추가 완료` 등 각 컬럼 추가 메시지
- `g5_mail_log 테이블 확인/생성 완료`

> 이미 설치된 상태에서 재실행해도 안전합니다(중복 실행 방지 처리).

### 3단계 — SMTP 설정

관리자 로그인 후 **관리자 메뉴 → 환경설정 → SMTP 설정** 으로 이동합니다.

| 항목 | 설명 |
|---|---|
| SMTP 사용 | 체크 시 SMTP 메일 발송 활성화 |
| SMTP 서버 | SMTP 호스트 주소 (예: `smtp.gmail.com`) |
| 포트 | SMTP 포트 번호 |
| 보안 방식 | `none` / `ssl` / `tls` 중 선택 |
| 아이디 | SMTP 인증 계정 |
| 비밀번호 | SMTP 인증 비밀번호 |
| 발신자 이름 | 메일에 표시될 발신자 이름 |
| 발신자 이메일 | 메일에 표시될 발신자 주소 |

---

## 주요 SMTP 서버 설정 예시

### Gmail

> Gmail은 2022년 이후 일반 비밀번호 대신 **앱 비밀번호** 사용 필요
> Google 계정 → 보안 → 2단계 인증 활성화 → 앱 비밀번호 생성

| 항목 | 값 |
|---|---|
| SMTP 서버 | `smtp.gmail.com` |
| 포트 | `465` (SSL) 또는 `587` (TLS) |
| 보안 방식 | `ssl` 또는 `tls` |
| 아이디 | Gmail 주소 전체 |
| 비밀번호 | 앱 비밀번호 (16자리) |

### Naver

| 항목 | 값 |
|---|---|
| SMTP 서버 | `smtp.naver.com` |
| 포트 | `465` |
| 보안 방식 | `ssl` |
| 아이디 | 네이버 아이디 |
| 비밀번호 | 네이버 비밀번호 |

> 네이버는 **IMAP/SMTP 사용 허용** 설정이 필요합니다.
> 네이버 메일 → 환경설정 → POP3/IMAP 설정 → IMAP/SMTP 사용 체크
> 네이버는 2단계인증 설정 후 **앱 비밀번호** 사용 필요

### SendGrid

| 항목 | 값 |
|---|---|
| SMTP 서버 | `smtp.sendgrid.net` |
| 포트 | `587` |
| 보안 방식 | `tls` |
| 아이디 | `apikey` (고정값) |
| 비밀번호 | SendGrid API Key |

---

## 메일 발송 로그

관리자 메뉴 → **메일 발송 로그** 에서 확인 가능합니다.

- 수신자, 제목, 발송 시간, 성공/실패 상태, 오류 메시지 표시
- **제목 클릭** 시 팝업으로 메일 원본 본문 내용 확인 가능

---

## 동작 원리

그누보드5의 `extend` 메커니즘을 이용합니다. `extend/smtp.extend.php` 파일이 자동으로 로드되어 아래 훅을 등록합니다.

| 훅 | 역할 |
|---|---|
| `admin_menu` | 관리자 메뉴에 SMTP 설정 / 메일 발송 로그 항목 추가 |
| `mail_options` | 그누보드 PHPMailer 객체에 SMTP 설정 주입 |
| `mail_send_result` | 메일 발송 결과를 DB 로그 테이블에 저장 |

**코어 파일은 전혀 수정하지 않으므로** 그누보드 업데이트 시 플러그인을 재설치할 필요가 없습니다.

---

## 요구 사항

| 항목 | 조건 |
|---|---|
| 그누보드5 | 5.5.x 이상 (PHPMailer 내장 버전) |
| PHP | 7.0 이상 |
| MySQL / MariaDB | 5.5 이상 |
| 서버 | 외부 SMTP 포트(465 또는 587) 아웃바운드 허용 |

---

## 제거 방법

1. 아래 파일/폴더 삭제

```
extend/smtp.extend.php
plugin/smtp_manager/
adm/smtp_manager_config.php
adm/smtp_manager_config_update.php
adm/smtp_manager_test_mail.php
adm/smtp_manager_log_view.php
```

2. DB 정리 (선택사항)

```sql
-- 메일 로그 테이블 삭제
DROP TABLE IF EXISTS `g5_mail_log`;

-- config 테이블에서 추가된 컬럼 삭제
ALTER TABLE `g5_config`
  DROP COLUMN IF EXISTS `smtp_use`,
  DROP COLUMN IF EXISTS `smtp_host`,
  DROP COLUMN IF EXISTS `smtp_port`,
  DROP COLUMN IF EXISTS `smtp_secure`,
  DROP COLUMN IF EXISTS `smtp_user`,
  DROP COLUMN IF EXISTS `smtp_pass`,
  DROP COLUMN IF EXISTS `smtp_from_name`,
  DROP COLUMN IF EXISTS `smtp_from_email`;
```

> 테이블 접두사(`g5_`)는 실제 환경에 맞게 변경하세요.

---

## 라이선스

```
Copyright (c) 2026 K3SOFT / WizardOfCode

이 소프트웨어는 다음 조건 하에 사용이 허가됩니다:

[허용]
- 소스 코드 무제한 열람, 수정, 커스터마이징
- 수정된 버전을 자신의 사이트 또는 고객 사이트에 설치·배포
- 타 솔루션·패키지에 포함하여 배포

[금지]
- 이 프로그램 자체(또는 실질적으로 동일한 형태)만을 단독으로
  재판매하는 행위

위 조건을 준수하는 한 상업적 이용이 가능합니다.
```

---

## 지원 및 문의

- **제작사:** K3SOFT
- **회사명:** WizardOfCode
- 버그 리포트 및 기능 제안은 구매처(콘텐츠몰) 댓글 또는 1:1 문의를 이용해 주세요.

---

## History

- v1.1.0 — 로그 선택 삭제 기능 추가
  - 관리자 > 메일 발송 로그에서 체크박스로 다중 선택 후 삭제 가능

*SMTP Manager for Gnuboard5 v1.1.0 — WizardOfCode*
