/*UPC CORRECTO*/
                        if (Convert.ToInt64(txtUnitsScan.Text) == Convert.ToInt64(txtUnitsReq.Text) - 1)
                        {
                            /*TOTAL DE PRENDAS ESCANEADAS*/
                            #region TOTAL DE PRENDAS ESCANEADAS
                            contador = 0;
                            txtUPCScann.Text = string.Empty;
                            txtUnitsReq.Text = txtUnitsReq.Text;
                            txtUnitsScan.Text = "0";
                            txtUnitsRemai.Text = txtUnitsReq.Text;
                            dgvEscan.Rows[0].Selected = true;
                            dgvEscan.FirstDisplayedScrollingRowIndex = (0);
                            txtUnitsScan.Text = (0).ToString();
                            txtCartonRq.Text = "1";
                            txtCartonsPacked.Text = "0";
                            txtCartonsReamaining.Text = "1";
                            txtUPCScann.Focus();
                            string[] separadas;

                            separadas = cmbSizes.Text.Split('x');
                            switch (cmbProductCode.Text)
                            {
                                case "88":
                                    #region TARGET
                                    /*TOTAL DE PRENDAS ESCANEADAS*/
                                    contador = 0;
                                    txtUPCScann.Text = string.Empty;
                                    txtUnitsReq.Text = txtUnitsReq.Text;
                                    txtUnitsScan.Text = "0";
                                    txtUnitsRemai.Text = txtUnitsReq.Text;
                                    dgvEscan.Rows[0].Selected = true;
                                    dgvEscan.FirstDisplayedScrollingRowIndex = (0);
                                    txtUnitsScan.Text = (0).ToString();
                                    txtUPCScann.Focus();
                                    ListUpc = f.ConsultaUPC(po_numero, upc);
                                    id = ListUpc[0].id;
                                    upc = ListUpc[0].upc;

                                    List<ConsultaProductosTargetResult> t = f.ConsultaProductosTarget(cmbPO.Text, cmbPOItem.Text, cmbProductCode.Text, ListUpc[0].Size.ToString());
                                    if (t.Count > 0)
                                    {
                                        contador = 0;
                                        txtCartonSize.Text = "";
                                        txtSize.Text = "";
                                        txtProductCode.Text = t[0].estilo.ToString();
                                        anterior.Add(id);
                                        upc = cmbPOItem.Text;

                                        EtiquetaZUMIES clase = new EtiquetaZUMIES();
                                        clase.id = t[0].id;
                                        clase.poInCompleto = Convert.ToDecimal(cmbPO.Text);
                                        clase.poItem = "88";
                                        clase.ProductCode = t[0].po_numero;
                                        clase.Size = t[0].Talla;
                                        clase.size_izquierdo = ListUpc[0].Size.ToString();
                                        clase.size_derecho = "";
                                        clase.TipoCarton = "0";
                                        clase.upc = t[0].upc;
                                        clase.Fecha = DateTime.Now;
                                        clase.CartonLeft = "";
                                        clase.CartonRight = "";
                                        clase.Cantidad = Convert.ToDecimal(t[0].cantidad);
                                        clase.Carton = 0;
                                        clase.usuario = usu[0].nombre;
                                        clase.id_cliente = cmbCliente.Text == "NA" ? 1 : Convert.ToInt32(cmbCliente.SelectedValue);
                                        clase.id_factura = cmbFactura.Text == "NA" ? 1 : Convert.ToInt32(cmbFactura.SelectedValue);
                                        clase.id_terminado = cmbTerminado.Text == "NA" ? 1 : Convert.ToInt32(cmbTerminado.SelectedValue);
                                        clase.cliente = cmbCliente.Text;
                                        clase.factura = cmbFactura.Text;
                                        clase.terminado = cmbTerminado.Text;
                                        clase.Vendor = t[0].itemDescription;
                                        clase.ESTILO = t[0].estilo;
                                        clase.DESCRIPTION = t[0].itemDescription;
                                        clase.QUANTITY = Convert.ToString(t[0].cantidad);
                                        clase.COUNTRY = "MEXICO";
                                        clase.po = Convert.ToDecimal(cmbPO.Text);
                                        clase.id_Inventario = f.GuardaInventarioZumies(clase, usu[0].id);

                                        id_InventarioAnt = clase.id_Inventario;
                                        int idIndex = Convert.ToInt32(cmbSizes.SelectedIndex);
                                        try
                                        {
                                            List<ConsultaInventarioIDResult> x1 = f.ConsultaInventarioID(id_InventarioAnt);
                                            if (x1.Count > 0)
                                            {
                                                List<ConsultaProductosTargetResult> x2 = f.ConsultaProductosTarget(cmbPO.Text, cmbPOItem.Text, cmbProductCode.Text, ListUpc[0].Size.ToString());

                                                /***/
                                                if (x2.Count > 0)
                                                {
                                                    contador = 0;
                                                    dgvEscan.DataSource = x2;
                                                    txtCartonsPacked.Text = x2[0].NumeroCaja.ToString();
                                                    txtCartonsReamaining.Text = Convert.ToString(Convert.ToInt32(x2[0].NumeroCaja.Value.ToString()));
                                                    txtCartonNumber.Text = x2[0].NumeroCaja.ToString();
                                                    //txtCartonRq.Text = x2[0].cantidadCajas.ToString();
                                                    txtCartonSize.Text = x2[0].Talla.ToString();
                                                    dgvEscan.Columns["Cantidad"].Visible = false;
                                                    dgvEscan.Columns["escaneado"].Visible = false;
                                                    dgvEscan.Columns["NumeroCaja"].Visible = false;
                                                    dgvEscan.Columns["itemDescription"].Visible = false;
                                                    dgvEscan.Columns["id"].Visible = false;
                                                    txtSize.Text = x2[0].Talla.ToString();
                                                    txtProductCode.Text = x2[0].estilo.ToString();
                                                    id = x2[0].id;
                                                    anterior.Add(id);
                                                    upc = x2[0].upc.ToString();
                                                    vendor = x2[0].itemDescription.ToString();
                                                    ///po = Convert.ToInt32(cmbPO.Text);
                                                    cantidad = x2[0].cantidad.ToString();
                                                    txtUnitsReq.Text = x2[0].cantidad.ToString();
                                                    txtUnitsRemai.Text = cantidad.ToString();
                                                    txtUnitsScan.Text = "0";
                                                    txtUPCScann.Focus();
                                                }
                                                iniciando = true;
                                                f.ConsultaPOItem(cmbPOItem, cmbPO.Text);
                                                cmbPOItem.SelectedIndex = 0;
                                                f.ConsultaProductCode(cmbProductCode, cmbPO.Text, cmbPOItem.Text);
                                                cmbProductCode.SelectedIndex = 0;
                                                f.ConsultaSizes(cmbSizes, cmbPO.Text, cmbPOItem.Text, cmbProductCode.Text);
                                                cmbSizes.SelectedText = ListUpc[0].Size;
                                                contador = 0;
                                                EtiquetaZUMIES claseZ = new EtiquetaZUMIES();
                                                claseZ.id = x1[0].id;
                                                claseZ.assembly = x1[0].Assembly;
                                                claseZ.poInCompleto = x1[0].poInCompleto;
                                                claseZ.poItem = "88";
                                                claseZ.ProductCode = x1[0].ProductCode;
                                                claseZ.Size = x1[0].size_izquierdo;
                                                claseZ.ESTILO = x1[0].ProductCode;
                                                claseZ.QUANTITY = Convert.ToString(x1[0].Cantidad);
                                                claseZ.cn_tag_num = Convert.ToInt32(x1[0].Carton);
                                                claseZ.CARTON_NUMBER_INICIAL = id_InventarioAnt.ToString();
                                                claseZ.CARTON_NUMBER_FINAL = "___";
                                                claseZ.COUNTRY = "MEXICO";
                                                claseZ.Carton = id_InventarioAnt;
                                                claseZ.Cantidad = Convert.ToDecimal(claseZ.QUANTITY);
                                                claseZ.DPCI = x1[0].TipoCarton;
                                                claseZ.itemDescription = claseZ.DPCI;
                                                claseZ.color = "";
                                                claseZ.size_izquierdo = ListUpc[0].Size.ToString();
                                                claseZ.assembly = x1[0].ProductCode;
                                                string contarDigitos = "00" + clase.upc.Substring(0, 11) + "1";

                                                List<EtiquetaZUMIES> listClase = new List<EtiquetaZUMIES>();
                                                BarcodeLib.Barcode Codigo = new BarcodeLib.Barcode { IncludeLabel = true, LabelFont = new Font("Arial", 14, FontStyle.Bold) };
                                                Codigo.BarWidth = 3;

                                                Image codigoBarras = Codigo.Encode(BarcodeLib.TYPE.ITF14, contarDigitos, Color.Black, Color.White, 350, 150);

                                                #region  RECORTANDO IMAGEN ESPERO NO VOLVER A USARLO
                                                // RECORTANDO IMAGEN ESPERO NO VOLVER A USARLO
                                                //Rectangle cropRec = new Rectangle(12, 0, 320, 200);
                                                //Image Original = codigoBarras;
                                                //Bitmap cropImage = new Bitmap(cropRec.Width, cropRec.Height);
                                                //Graphics g = Graphics.FromImage(cropImage);
                                                //g.DrawImage(Original, new Rectangle(0, 0, cropRec.Width, cropRec.Height), cropRec, GraphicsUnit.Pixel);
                                                //Original.Dispose();
                                                #endregion

                                                claseZ.codigoBarras = codigoBarras;  //cropImage;
                                                listClase.Add(claseZ);
                                                id_InventarioAnt = Convert.ToInt32(clase.id);
                                                ReportCajaTarget report = new ReportCajaTarget
                                                {
                                                    DataSource = listClase
                                                };
                                                // Disable margins warning. 
                                                report.PrintingSystem.ShowMarginsWarning = false;
                                                ReportPrintTool tool = new ReportPrintTool(report);

                                                tool.Print(); //imprime de golpe
                                            }
                                            LimpiarPantallaEscaneo();

                                        }
                                        catch (Exception ex)
                                        {
                                            MessageBox.Show(ex.Message.ToString());
                                        }
                                    }
                                    else
                                    {
                                        MessageBox.Show("Ya se escanearon todas las cajas de la Talla");
                                    }

                                    #endregion
                                    break;
                                case "98":

                                    break;
                                case "99":
                                    #region LEVIS
                                    List<ConsultaProductosNuevoResult> x = f.ConsultaProductos(cmbPO.Text, cmbPOItem.Text, cmbProductCode.Text, separadas[0].ToString(), separadas[1].ToString());
                                    int prodNuevosCont = 0;
                                    int NumeroCarton = 0;
                                    //dgvEscan.DataSource = x;
                                    foreach (ConsultaProductosNuevoResult a in x)
                                    {
                                        prodNuevosCont = prodNuevosCont + 1;
                                        contador = 0;
                                        txtCartonNumber.Text = a.CartonNumber.ToString();
                                        txtCartonSize.Text = "";
                                        txtSize.Text = "";
                                        txtProductCode.Text = a.ProductCode.ToString();

                                        anterior.Add(id);
                                        upc = cmbPOItem.Text;

                                        EtiquetaCajaModificada clase = new EtiquetaCajaModificada
                                        {
                                            id = a.id,
                                            po = Convert.ToDecimal(cmbPO.Text),
                                            poInCompleto = Convert.ToDecimal(cmbPO.Text),
                                            poItem = "99",
                                            ProductCode = a.ProductCode,
                                            Size = a.Size
                                        };
                                        separadas = a.Size.Split('x');

                                        if (a.id > 0)
                                        {
                                        }
                                        else
                                        {
                                            a.id = 1;
                                        }
                                        clase.size_derecho = separadas[1].ToString();
                                        clase.size_izquierdo = separadas[0].ToString();
                                        clase.TipoCarton = "0";
                                        clase.upc = a.UPC;
                                        clase.Fecha = DateTime.Now;
                                        clase.CartonLeft = "";
                                        clase.CartonRight = "";
                                        clase.Cantidad = Convert.ToDecimal(a.cantidad);
                                        DateTime DateObject = Convert.ToDateTime(DateTime.Now.ToString());
                                        string fecha = DateObject.Day.ToString() + DateObject.Month.ToString() + DateObject.Year.ToString();
                                        NumeroCarton = Convert.ToInt32(fecha);//Convert.ToInt32(a.CartonNumber + '0' + Convert.ToInt32(a.id));
                                        clase.TipoCarton = (1).ToString();
                                        clase.Carton = NumeroCarton;
                                        clase.usuario = usu[0].nombre;
                                        clase.id_cliente = cmbCliente.Text == "NA" ? 1 : Convert.ToInt32(cmbCliente.SelectedValue);
                                        clase.id_factura = cmbFactura.Text == "NA" ? 1 : Convert.ToInt32(cmbFactura.SelectedValue);
                                        clase.id_terminado = cmbTerminado.Text == "NA" ? 1 : Convert.ToInt32(cmbTerminado.SelectedValue);
                                        clase.cliente = cmbCliente.Text;
                                        clase.factura = cmbFactura.Text;
                                        clase.terminado = cmbTerminado.Text;
                                        clase.assembly = "";
                                        clase.Vendor = "";
                                        clase.ShipTo = "";
                                        clase.id_Inventario = f.GuardaInventario(clase, usu[0].id);
                                    }
                                    #endregion 
                                    break;
                            }
                        }
                        else
                        {
                            #region ESCANEANDO X PRENDA AUN
                            /*ESCANEANDO X PRENDA AUN*/
                            contador = contador + 1;
                            dgvEscan.Rows[(Convert.ToInt32(txtUnitsScan.Text) + 1)].Selected = true;
                            dgvEscan.FirstDisplayedScrollingRowIndex = (Convert.ToInt32(txtUnitsScan.Text) + 1);
                            txtUnitsScan.Text = (Convert.ToInt64(txtUnitsScan.Text) + 1).ToString();
                            txtUnitsRemai.Text = (Convert.ToInt64(txtUnitsRemai.Text) - 1).ToString();
                            txtUPCScann.Text = string.Empty;
                            txtUPCScann.Focus();
                            sonido.URL = Application.StartupPath + @"\mp3\correct.mp3";
                            #endregion
                        }